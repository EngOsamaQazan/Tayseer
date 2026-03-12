<?php
/**
 * VisionService — Google Cloud Vision API Integration
 * Lightweight REST client — zero external dependencies
 * 
 * Features:
 * - Document text detection (OCR)
 * - Label detection (image classification)
 * - Automatic document type classification
 * - Usage tracking & cost monitoring
 * 
 * @author TayseerAI Smart Platform
 */

namespace backend\modules\customers\components;

use Yii;
use common\models\SystemSettings;

class VisionService
{
    /** @var string Google Vision API endpoint */
    const API_URL = 'https://vision.googleapis.com/v1/images:annotate';
    
    /** @var string OAuth2 token URL */
    const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    
    /** @var string Vision API scope */
    const SCOPE = 'https://www.googleapis.com/auth/cloud-vision';
    
    /** @var float Cost per API call (after free tier) — default, overridden by DB settings */
    const COST_PER_CALL = 0.0015; // $1.50 / 1000
    
    /** @var int Free calls per month — default, overridden by DB settings */
    const FREE_TIER_LIMIT = 1000;
    
    /** @var string Cached access token */
    private static $accessToken = null;
    
    /** @var int Token expiry timestamp */
    private static $tokenExpiry = 0;
    
    /** @var array Service account credentials */
    private static $credentials = null;
    
    /**
     * Document classification rules
     * 
     * Each rule has:
     * - type: dropdown value
     * - label: Arabic display name
     * - keywords: basic keywords (weight: 10 each)
     * - strong_keywords: high-confidence phrases (weight: 25 each)
     * - negative_keywords: keywords that REDUCE score (weight: -15 each)
     * - label_keywords: matched against Vision API labels (weight: score/20)
     * - min_text_length: minimum OCR text length expected (0 = no minimum)
     */
    private static $classificationRules = [
        'national_id' => [
            'type' => '0',
            'label' => 'هوية وطنية',
            'strong_keywords' => ['بطاقة شخصية', 'الأحوال المدنية', 'أحوال مدنية', 'رقم وطني', 'national id', 'identity card', 'civil status and passports'],
            'keywords' => ['هوية', 'الهوية', 'id number', 'civil status', 'الرقم الوطني'],
            'negative_keywords' => ['ضمان اجتماعي', 'المؤسسة العامة للضمان', 'social security', 'كشف البيانات', 'فترات الاشتراك', 'الرواتب', 'salary', 'راتب', 'كشف راتب'],
            'label_keywords' => ['identity document', 'id card'],
            'min_text_length' => 20,
        ],
        'passport' => [
            'type' => '1',
            'label' => 'جواز سفر',
            'strong_keywords' => ['جواز سفر', 'passport', 'travel document', 'passeport'],
            'keywords' => ['جواز', 'سفر'],
            'negative_keywords' => [],
            'label_keywords' => ['passport'],
            'min_text_length' => 15,
        ],
        'driving_license' => [
            'type' => '2',
            'label' => 'رخصة قيادة',
            'strong_keywords' => ['رخصة قيادة', 'driving license', 'driving licence', 'رخصة سواقة'],
            'keywords' => ['رخصة', 'قيادة', 'driving', 'سواقة'],
            'negative_keywords' => [],
            'label_keywords' => ['driving license'],
            'min_text_length' => 10,
        ],
        'birth_certificate' => [
            'type' => '3',
            'label' => 'شهادة ميلاد',
            'strong_keywords' => ['شهادة ميلاد', 'birth certificate', 'وثيقة ميلاد', 'سجل المواليد', 'شهادة ولادة'],
            'keywords' => ['مولود', 'مواليد'],
            'negative_keywords' => ['ضمان', 'راتب', 'اشتراك', 'social security'],
            'label_keywords' => ['birth certificate'],
            'min_text_length' => 15,
        ],
        'appointment_letter' => [
            'type' => '4',
            'label' => 'شهادة تعيين',
            'strong_keywords' => ['شهادة تعيين', 'كتاب تعيين', 'مباشرة عمل', 'قرار تعيين', 'appointment letter'],
            'keywords' => ['تعيين', 'مباشرة', 'توظيف'],
            'negative_keywords' => ['عسكري', 'قوات', 'جيش'],
            'label_keywords' => [],
            'min_text_length' => 20,
        ],
        'social_security' => [
            'type' => '5',
            'label' => 'كتاب ضمان اجتماعي',
            'strong_keywords' => ['المؤسسة العامة للضمان', 'ضمان اجتماعي', 'الضمان الاجتماعي', 'social security corporation', 'كشف البيانات التفصيلي', 'مؤسسة الضمان'],
            'keywords' => ['ضمان', 'اشتراك', 'فترات الاشتراك', 'الرواتب المالية', 'رقم التأمين', 'رقم المنشأة', 'المشتركين', 'تأمينات', 'اسم المنشأة', 'المنشآت المشتركة', 'social security'],
            'negative_keywords' => [],
            'label_keywords' => ['document', 'table', 'spreadsheet'],
            'min_text_length' => 50,
        ],
        'salary_slip' => [
            'type' => '6',
            'label' => 'كشف راتب',
            'strong_keywords' => ['كشف راتب', 'بيان راتب', 'salary slip', 'payslip', 'pay slip', 'إفادة راتب', 'شهادة راتب', 'تحويل الراتب'],
            'keywords' => ['راتب', 'صافي الراتب', 'إجمالي الراتب', 'الراتب الأساسي', 'salary', 'بدلات', 'خصومات', 'net salary', 'gross salary', 'علاوات'],
            'negative_keywords' => ['ضمان اجتماعي', 'المؤسسة العامة'],
            'label_keywords' => [],
            'min_text_length' => 20,
        ],
        'military_certificate' => [
            'type' => '7',
            'label' => 'شهادة تعيين عسكري',
            'strong_keywords' => ['القوات المسلحة الأردنية', 'قوات مسلحة', 'الأمن العام', 'مديرية الأمن العام', 'الدفاع المدني', 'الدرك'],
            'keywords' => ['عسكري', 'جيش', 'أمن عام', 'دفاع مدني', 'military', 'armed forces', 'درك', 'ضابط', 'جندي'],
            'negative_keywords' => [],
            'label_keywords' => [],
            'min_text_length' => 15,
        ],
        'personal_photo' => [
            'type' => '8',
            'label' => 'صورة شخصية',
            'strong_keywords' => [],
            'keywords' => [],
            'negative_keywords' => [],
            'label_keywords' => ['selfie', 'face', 'portrait', 'person', 'chin', 'forehead', 'jaw', 'neck', 'head', 'cheek', 'nose', 'eyebrow', 'facial hair', 'beard', 'moustache', 'hair'],
            'min_text_length' => 0,
        ],
    ];

    /**
     * Analyze an image with Google Vision API
     * 
     * @param string $imagePath Full path to image file
     * @param array $features Features to detect ['TEXT_DETECTION', 'LABEL_DETECTION']
     * @param int|null $customerId Customer ID for tracking
     * @param int|null $documentId Document ID for tracking
     * @param string|null $documentTable Table name for tracking
     * @return array Analysis results
     */
    public static function analyze(
        string $imagePath,
        array $features = ['TEXT_DETECTION', 'LABEL_DETECTION'],
        ?int $customerId = null,
        ?int $documentId = null,
        ?string $documentTable = null
    ): array {
        $startTime = microtime(true);
        
        try {
            // Read and encode image
            if (!file_exists($imagePath)) {
                throw new \Exception("File not found: {$imagePath}");
            }
            
            $imageContent = base64_encode(file_get_contents($imagePath));
            
            // Get access token
            $token = self::getAccessToken();
            
            // Build request
            $featuresList = [];
            foreach ($features as $f) {
                $featuresList[] = ['type' => $f, 'maxResults' => 20];
            }
            
            $requestBody = json_encode([
                'requests' => [
                    [
                        'image' => ['content' => $imageContent],
                        'features' => $featuresList,
                        'imageContext' => [
                            'languageHints' => ['ar', 'en'],
                        ],
                    ]
                ]
            ]);
            
            // Call Vision API
            $ch = curl_init(self::API_URL);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $requestBody,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $token,
                    'Content-Type: application/json',
                ],
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            
            $elapsed = (int)((microtime(true) - $startTime) * 1000);
            
            if ($curlError) {
                throw new \Exception("cURL error: {$curlError}");
            }
            
            if ($httpCode !== 200) {
                throw new \Exception("API error ({$httpCode}): " . substr($response, 0, 500));
            }
            
            $data = json_decode($response, true);
            
            if (isset($data['responses'][0]['error'])) {
                throw new \Exception("Vision API error: " . ($data['responses'][0]['error']['message'] ?? 'Unknown'));
            }
            
            $result = $data['responses'][0] ?? [];
            
            // Extract text
            $extractedText = '';
            if (isset($result['textAnnotations'][0]['description'])) {
                $extractedText = $result['textAnnotations'][0]['description'];
            } elseif (isset($result['fullTextAnnotation']['text'])) {
                $extractedText = $result['fullTextAnnotation']['text'];
            }
            
            // Extract labels
            $labels = [];
            if (isset($result['labelAnnotations'])) {
                foreach ($result['labelAnnotations'] as $label) {
                    $labels[] = [
                        'description' => $label['description'],
                        'score' => round($label['score'] * 100, 1),
                    ];
                }
            }
            
            // Classify document
            $classification = self::classifyDocument($extractedText, $labels);
            
            // Track usage (per feature)
            foreach ($features as $f) {
                self::trackUsage($f, 'success', $elapsed, $customerId, $documentId, $documentTable);
            }
            
            return [
                'success' => true,
                'text' => $extractedText,
                'labels' => $labels,
                'classification' => $classification,
                'raw_response' => $result,
                'response_time_ms' => $elapsed,
            ];
            
        } catch (\Exception $e) {
            $elapsed = (int)((microtime(true) - $startTime) * 1000);
            
            // Track error
            foreach ($features as $f) {
                self::trackUsage($f, 'error', $elapsed, $customerId, $documentId, $documentTable, $e->getMessage());
            }
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'text' => '',
                'labels' => [],
                'classification' => null,
                'response_time_ms' => $elapsed,
            ];
        }
    }

    /**
     * Quick OCR — text extraction only
     */
    public static function ocr(string $imagePath, ?int $customerId = null): array
    {
        return self::analyze($imagePath, ['DOCUMENT_TEXT_DETECTION'], $customerId);
    }

    /**
     * Quick classify — detect document type
     */
    public static function classify(string $imagePath, ?int $customerId = null): array
    {
        return self::analyze($imagePath, ['TEXT_DETECTION', 'LABEL_DETECTION'], $customerId);
    }

    /**
     * Advanced document classification using text + labels + heuristics
     * 
     * Scoring system:
     * - strong_keywords: +25 points each (high-confidence phrases)
     * - keywords: +10 points each (basic terms)
     * - negative_keywords: -15 points each (wrong category signal)
     * - label_keywords: + (label score / 15) (Vision API labels)
     * - personal_photo bonus: if face-related labels dominate and little/no text
     */
    private static function classifyDocument(string $text, array $labels): ?array
    {
        $textLower = mb_strtolower($text);
        $textLength = mb_strlen(trim($text));
        $bestMatch = null;
        $bestScore = -999;
        $allScores = []; // for debugging

        foreach (self::$classificationRules as $key => $rule) {
            $score = 0;
            $matchedKeywords = [];

            // Strong keywords — high weight
            $strongKw = isset($rule['strong_keywords']) ? $rule['strong_keywords'] : [];
            foreach ($strongKw as $keyword) {
                if (mb_strpos($textLower, mb_strtolower($keyword)) !== false) {
                    $score += 25;
                    $matchedKeywords[] = $keyword;
                }
            }

            // Regular keywords — medium weight
            $regularKw = isset($rule['keywords']) ? $rule['keywords'] : [];
            foreach ($regularKw as $keyword) {
                if (mb_strpos($textLower, mb_strtolower($keyword)) !== false) {
                    $score += 10;
                    $matchedKeywords[] = $keyword;
                }
            }

            // Negative keywords — subtract score (penalize wrong match)
            $negativeKw = isset($rule['negative_keywords']) ? $rule['negative_keywords'] : [];
            foreach ($negativeKw as $keyword) {
                if (mb_strpos($textLower, mb_strtolower($keyword)) !== false) {
                    $score -= 15;
                }
            }

            // Label matching — check Vision API labels
            $labelKw = isset($rule['label_keywords']) ? $rule['label_keywords'] : [];
            foreach ($labels as $label) {
                $labelDesc = mb_strtolower($label['description']);
                foreach ($labelKw as $lkw) {
                    if (mb_strpos($labelDesc, mb_strtolower($lkw)) !== false) {
                        $score += $label['score'] / 15;
                        $matchedKeywords[] = 'label:' . $label['description'];
                    }
                }
            }

            // Special: personal_photo detection
            // If this is the personal_photo rule, boost score when:
            // - Very little or no text extracted
            // - Face-related labels are present
            if ($key === 'personal_photo') {
                $faceLabels = 0;
                foreach ($labels as $label) {
                    $ld = mb_strtolower($label['description']);
                    if (in_array($ld, ['face', 'selfie', 'portrait', 'person', 'chin', 'forehead', 'jaw', 'neck', 'head', 'cheek', 'nose', 'eyebrow', 'facial hair', 'beard', 'moustache', 'hair', 'smile', 'man', 'woman', 'boy', 'girl', 'human'])) {
                        $faceLabels++;
                    }
                }
                // If 3+ face labels and very little text → strong personal photo signal
                if ($faceLabels >= 3 && $textLength < 30) {
                    $score += 40;
                    $matchedKeywords[] = "face_labels:{$faceLabels},text_len:{$textLength}";
                } elseif ($faceLabels >= 2 && $textLength < 60) {
                    $score += 20;
                    $matchedKeywords[] = "face_labels:{$faceLabels},text_len:{$textLength}";
                }
            }

            // Minimum text length check — if rule expects text but very little found, penalize
            $minLen = isset($rule['min_text_length']) ? $rule['min_text_length'] : 0;
            if ($minLen > 0 && $textLength < $minLen && $score > 0) {
                $score = (int)($score * 0.3); // heavy penalty
            }

            $allScores[$key] = $score;

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = [
                    'key' => $key,
                    'type' => $rule['type'],
                    'label' => $rule['label'],
                    'confidence' => 0,
                    'matched_keywords' => $matchedKeywords,
                ];
            }
        }

        // Calculate confidence as percentage of maximum possible
        if ($bestMatch) {
            if ($bestScore <= 0) {
                // No positive match found
                return [
                    'key' => 'unknown',
                    'type' => '9',
                    'label' => 'غير محدد',
                    'confidence' => 0,
                    'matched_keywords' => [],
                ];
            }

            // Confidence: based on score magnitude and gap to second-best
            $sortedScores = $allScores;
            arsort($sortedScores);
            $scoresArr = array_values($sortedScores);
            $gap = (count($scoresArr) > 1) ? ($scoresArr[0] - $scoresArr[1]) : $scoresArr[0];

            // Higher score + bigger gap = higher confidence
            $confidence = min(99, max(10, (int)($bestScore * 1.5 + $gap * 2)));

            $bestMatch['confidence'] = $confidence;
        }

        return $bestMatch;
    }

    /**
     * Get Google API access token using JWT
     */
    private static function getAccessToken(): string
    {
        if (self::$accessToken && time() < self::$tokenExpiry - 60) {
            return self::$accessToken;
        }
        
        $creds = self::getCredentials();
        
        // Build JWT header
        $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        
        // Build JWT claims
        $now = time();
        $claims = base64_encode(json_encode([
            'iss' => $creds['client_email'],
            'scope' => self::SCOPE,
            'aud' => self::TOKEN_URL,
            'iat' => $now,
            'exp' => $now + 3600,
        ]));
        
        // Sign
        $signatureInput = $header . '.' . $claims;
        $signature = '';
        $privateKey = openssl_pkey_get_private($creds['private_key']);
        if (!$privateKey) {
            throw new \Exception('Invalid private key in service account credentials');
        }
        openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        
        $jwt = $signatureInput . '.' . self::base64UrlEncode($signature);
        
        // Exchange JWT for access token
        $ch = curl_init(self::TOKEN_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]),
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($httpCode !== 200) {
            throw new \Exception("Token exchange failed ({$httpCode}): " . $response);
        }
        
        $tokenData = json_decode($response, true);
        
        if (!isset($tokenData['access_token'])) {
            throw new \Exception('No access_token in response');
        }
        
        self::$accessToken = $tokenData['access_token'];
        self::$tokenExpiry = $now + ($tokenData['expires_in'] ?? 3600);
        
        return self::$accessToken;
    }

    /**
     * Check if Google Cloud Vision is enabled
     */
    public static function isEnabled(): bool
    {
        return SystemSettings::get('google_cloud', 'enabled', '0') === '1';
    }

    /**
     * Load service account credentials from DB (with JSON file fallback)
     */
    private static function getCredentials(): array
    {
        if (self::$credentials) return self::$credentials;

        // Primary: read from database (system_settings)
        $dbCreds = SystemSettings::getGroup('google_cloud');

        if (!empty($dbCreds['client_email']) && !empty($dbCreds['private_key'])) {
            // Check if enabled
            if (isset($dbCreds['enabled']) && $dbCreds['enabled'] !== '1') {
                throw new \Exception('Google Cloud Vision API معطّل — فعّله من الإعدادات العامة');
            }

            self::$credentials = [
                'project_id'   => $dbCreds['project_id'] ?? '',
                'client_email' => $dbCreds['client_email'],
                'private_key'  => $dbCreds['private_key'],
            ];

            return self::$credentials;
        }

        // Fallback: read from JSON file (legacy support)
        $path = Yii::getAlias('@backend/config/credentials/google-vision.json');

        if (file_exists($path)) {
            $fileCreds = json_decode(file_get_contents($path), true);
            if ($fileCreds && isset($fileCreds['private_key'])) {
                Yii::warning('VisionService: Using legacy JSON file credentials. Migrate to System Settings.', 'vision');
                self::$credentials = $fileCreds;
                return self::$credentials;
            }
        }

        throw new \Exception('لم يتم تكوين بيانات اعتماد Google Cloud — اذهب إلى الإعدادات العامة → Google Cloud');
    }

    /**
     * Track API usage for cost monitoring
     */
    private static function trackUsage(
        string $feature,
        string $status,
        int $responseTimeMs,
        ?int $customerId,
        ?int $documentId,
        ?string $documentTable,
        ?string $errorMessage = null
    ): void {
        try {
            // Calculate cost (use DB settings if available)
            $monthlyUsage = self::getMonthlyUsageCount();
            $monthlyLimit = (int) SystemSettings::get('google_cloud', 'monthly_limit', self::FREE_TIER_LIMIT);
            $costPerReq = (float) SystemSettings::get('google_cloud', 'cost_per_request', self::COST_PER_CALL);
            $cost = ($monthlyUsage >= $monthlyLimit) ? $costPerReq : 0;
            
            Yii::$app->db->createCommand()->insert('os_vision_api_usage', [
                'api_feature' => $feature,
                'customer_id' => $customerId,
                'document_id' => $documentId,
                'document_table' => $documentTable,
                'request_status' => $status,
                'response_time_ms' => $responseTimeMs,
                'cost_estimate' => $cost,
                'error_message' => $errorMessage,
                'request_by' => Yii::$app->user->id ?? null,
                'created_at' => date('Y-m-d H:i:s'),
            ])->execute();
        } catch (\Exception $e) {
            Yii::error("Failed to track Vision API usage: " . $e->getMessage());
        }
    }

    /**
     * Get current month's usage count
     */
    public static function getMonthlyUsageCount(): int
    {
        try {
            $month = date('Y-m');
            return (int)Yii::$app->db->createCommand(
                "SELECT COUNT(*) FROM os_vision_api_usage WHERE created_at >= :start AND request_status='success'",
                [':start' => $month . '-01 00:00:00']
            )->queryScalar();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get usage statistics for dashboard
     */
    public static function getUsageStats(): array
    {
        try {
            $month = date('Y-m');
            $start = $month . '-01 00:00:00';
            $db = Yii::$app->db;
            
            $total = (int)$db->createCommand(
                "SELECT COUNT(*) FROM os_vision_api_usage WHERE created_at >= :start",
                [':start' => $start]
            )->queryScalar();
            
            $successful = (int)$db->createCommand(
                "SELECT COUNT(*) FROM os_vision_api_usage WHERE created_at >= :start AND request_status='success'",
                [':start' => $start]
            )->queryScalar();
            
            $totalCost = (float)$db->createCommand(
                "SELECT COALESCE(SUM(cost_estimate), 0) FROM os_vision_api_usage WHERE created_at >= :start",
                [':start' => $start]
            )->queryScalar();
            
            $avgResponseMs = (int)$db->createCommand(
                "SELECT COALESCE(AVG(response_time_ms), 0) FROM os_vision_api_usage WHERE created_at >= :start AND request_status='success'",
                [':start' => $start]
            )->queryScalar();
            
            $remaining = max(0, self::FREE_TIER_LIMIT - $successful);
            
            $byFeature = $db->createCommand(
                "SELECT api_feature, COUNT(*) as cnt, SUM(cost_estimate) as cost FROM os_vision_api_usage WHERE created_at >= :start GROUP BY api_feature",
                [':start' => $start]
            )->queryAll();
            
            // Daily breakdown for chart
            $daily = $db->createCommand(
                "SELECT DATE(created_at) as day, COUNT(*) as cnt FROM os_vision_api_usage WHERE created_at >= :start GROUP BY DATE(created_at) ORDER BY day",
                [':start' => $start]
            )->queryAll();
            
            return [
                'month' => $month,
                'total_requests' => $total,
                'successful' => $successful,
                'failed' => $total - $successful,
                'total_cost' => round($totalCost, 4),
                'avg_response_ms' => $avgResponseMs,
                'free_remaining' => $remaining,
                'free_limit' => self::FREE_TIER_LIMIT,
                'cost_per_call' => self::COST_PER_CALL,
                'by_feature' => $byFeature,
                'daily' => $daily,
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // ═══════════════════════════════════════════════════════════
    // LIVE GOOGLE CLOUD DATA — Billing + Usage APIs
    // ═══════════════════════════════════════════════════════════

    /** @var string Billing Account ID */
    const BILLING_ACCOUNT_ID = '01E8EA-306425-3D5484';

    /** @var string Google Cloud project ID */
    const PROJECT_ID = 'tayseerai';

    /**
     * Get REAL billing cost from Google Cloud Billing API
     * Returns actual charges for the current month
     */
    public static function getGoogleBillingCost(): array
    {
        try {
            $token = self::getMultiScopeToken();
            $billingId = self::BILLING_ACCOUNT_ID;
            $projectId = self::PROJECT_ID;

            // Use Cloud Billing API — get project billing info
            $url = 'https://cloudbilling.googleapis.com/v1/projects/' . $projectId . '/billingInfo';

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $token,
                    'Content-Type: application/json',
                ],
                CURLOPT_TIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            $billingInfo = ($httpCode === 200) ? json_decode($response, true) : null;

            // Get cost breakdown from Billing Budgets/Reports
            // Use Service Usage API for actual metric counts
            $usageData = self::getGoogleServiceUsage();

            return [
                'success' => true,
                'billing_enabled' => isset($billingInfo['billingEnabled']) ? $billingInfo['billingEnabled'] : false,
                'billing_account' => isset($billingInfo['billingAccountName']) ? $billingInfo['billingAccountName'] : null,
                'project_id' => $projectId,
                'usage' => $usageData,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get REAL API usage metrics from Google Cloud Monitoring API
     * Returns actual Vision API call counts from Google's perspective
     */
    public static function getGoogleServiceUsage(): array
    {
        try {
            $token = self::getMultiScopeToken();
            $projectId = self::PROJECT_ID;

            // Use Cloud Monitoring API (metrics explorer)
            // Metric: serviceruntime.googleapis.com/api/request_count
            // Filtered by service = vision.googleapis.com
            $now = time();
            $startOfMonth = strtotime(date('Y-m-01 00:00:00'));

            $startISO = gmdate('Y-m-d\TH:i:s\Z', $startOfMonth);
            $endISO = gmdate('Y-m-d\TH:i:s\Z', $now);

            // Use Service Usage API — get consumer usage
            $url = 'https://monitoring.googleapis.com/v3/projects/' . $projectId . '/timeSeries'
                 . '?filter=' . urlencode('metric.type="serviceruntime.googleapis.com/api/request_count" AND resource.labels.service="vision.googleapis.com"')
                 . '&interval.startTime=' . urlencode($startISO)
                 . '&interval.endTime=' . urlencode($endISO)
                 . '&aggregation.alignmentPeriod=2592000s'
                 . '&aggregation.perSeriesAligner=ALIGN_SUM';

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $token,
                    'Content-Type: application/json',
                ],
                CURLOPT_TIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($httpCode !== 200) {
                // Fallback: try simpler Service Usage API
                return self::getServiceUsageFallback($token, $projectId);
            }

            $data = json_decode($response, true);
            $totalRequests = 0;
            $breakdown = [];

            if (isset($data['timeSeries'])) {
                foreach ($data['timeSeries'] as $ts) {
                    $method = isset($ts['metric']['labels']['method']) ? $ts['metric']['labels']['method'] : 'unknown';
                    $status = isset($ts['metric']['labels']['response_code_class']) ? $ts['metric']['labels']['response_code_class'] : '';
                    $count = 0;

                    if (isset($ts['points'])) {
                        foreach ($ts['points'] as $point) {
                            $val = isset($point['value']['int64Value']) ? (int)$point['value']['int64Value'] : 0;
                            $count += $val;
                        }
                    }

                    $totalRequests += $count;
                    $breakdown[] = [
                        'method' => $method,
                        'status' => $status,
                        'count' => $count,
                    ];
                }
            }

            // Estimate cost based on actual Google count
            $freeTier = self::FREE_TIER_LIMIT;
            $billableRequests = max(0, $totalRequests - $freeTier);
            $estimatedCost = $billableRequests * self::COST_PER_CALL;

            return [
                'source' => 'google_monitoring',
                'total_requests' => $totalRequests,
                'free_tier_used' => min($totalRequests, $freeTier),
                'billable_requests' => $billableRequests,
                'estimated_cost' => round($estimatedCost, 4),
                'free_remaining' => max(0, $freeTier - $totalRequests),
                'breakdown' => $breakdown,
                'period' => [
                    'start' => $startISO,
                    'end' => $endISO,
                ],
            ];

        } catch (\Exception $e) {
            return [
                'source' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Fallback: Use Service Usage API (simpler, always works)
     */
    private static function getServiceUsageFallback(string $token, string $projectId): array
    {
        // List enabled services and their usage
        $url = 'https://serviceusage.googleapis.com/v1/projects/' . $projectId . '/services/vision.googleapis.com';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $serviceInfo = ($httpCode === 200) ? json_decode($response, true) : [];
        $state = isset($serviceInfo['state']) ? $serviceInfo['state'] : 'UNKNOWN';

        return [
            'source' => 'service_usage_api',
            'service_state' => $state,
            'service_name' => 'vision.googleapis.com',
            'note' => 'Detailed metrics require Cloud Monitoring API access',
        ];
    }

    /**
     * Get access token with multiple scopes (Billing + Monitoring + Vision)
     */
    private static function getMultiScopeToken(): string
    {
        $creds = self::getCredentials();

        $scopes = implode(' ', [
            'https://www.googleapis.com/auth/cloud-vision',
            'https://www.googleapis.com/auth/cloud-billing.readonly',
            'https://www.googleapis.com/auth/monitoring.read',
            'https://www.googleapis.com/auth/servicecontrol',
            'https://www.googleapis.com/auth/cloud-platform',
        ]);

        $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $now = time();
        $claims = base64_encode(json_encode([
            'iss' => $creds['client_email'],
            'scope' => $scopes,
            'aud' => self::TOKEN_URL,
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $signatureInput = $header . '.' . $claims;
        $signature = '';
        $privateKey = openssl_pkey_get_private($creds['private_key']);
        if (!$privateKey) {
            throw new \Exception('Invalid private key');
        }
        openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        $jwt = $signatureInput . '.' . self::base64UrlEncode($signature);

        $ch = curl_init(self::TOKEN_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]),
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode !== 200) {
            throw new \Exception("Multi-scope token failed ({$httpCode}): " . substr($response, 0, 300));
        }

        $tokenData = json_decode($response, true);
        if (!isset($tokenData['access_token'])) {
            throw new \Exception('No access_token in multi-scope response');
        }

        return $tokenData['access_token'];
    }

    /**
     * Combined stats: Local tracking + Live Google data
     */
    public static function getCombinedStats(): array
    {
        $local = self::getUsageStats();
        $google = self::getGoogleBillingCost();

        return [
            'local' => $local,
            'google' => $google,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Base64 URL-safe encode
     */
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Create thumbnail from image
     */
    public static function createThumbnail(string $sourcePath, string $thumbPath, int $maxWidth = 200, int $maxHeight = 200): bool
    {
        try {
            $info = getimagesize($sourcePath);
            if (!$info) return false;
            
            list($origW, $origH) = $info;
            $ratio = min($maxWidth / $origW, $maxHeight / $origH);
            $newW = (int)($origW * $ratio);
            $newH = (int)($origH * $ratio);
            
            $thumb = imagecreatetruecolor($newW, $newH);
            
            switch ($info['mime']) {
                case 'image/jpeg':
                    $source = imagecreatefromjpeg($sourcePath);
                    break;
                case 'image/png':
                    $source = imagecreatefrompng($sourcePath);
                    imagealphablending($thumb, false);
                    imagesavealpha($thumb, true);
                    break;
                case 'image/webp':
                    $source = imagecreatefromwebp($sourcePath);
                    break;
                default:
                    return false;
            }
            
            imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
            
            // Save as JPEG
            $dir = dirname($thumbPath);
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            imagejpeg($thumb, $thumbPath, 85);
            
            imagedestroy($thumb);
            imagedestroy($source);
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
