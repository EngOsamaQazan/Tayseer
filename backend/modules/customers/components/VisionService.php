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

    /** @var string Scope for Generative Language API (Gemini) */
    const SCOPE_GEMINI = 'https://www.googleapis.com/auth/generative-language';

    /** @var array Gemini models in priority order (fallback chain) */
    const GEMINI_MODELS = ['gemini-2.5-flash', 'gemini-2.5-flash-lite', 'gemini-2.0-flash'];

    /** @var string Generative Language API endpoint template */
    const GEMINI_ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';

    /** @var float Cost per API call (after free tier) — default, overridden by DB settings */
    const COST_PER_CALL = 0.0015; // $1.50 / 1000
    
    /** @var int Free calls per month — default, overridden by DB settings */
    const FREE_TIER_LIMIT = 1000;
    
    /** @var array Cached access tokens keyed by scope */
    private static $tokens = [];

    /** @var string Cached access token (legacy compat) */
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
     * Get an OAuth2 access token for a specific scope (with caching).
     */
    private static function getTokenForScope(string $scope): string
    {
        if (isset(self::$tokens[$scope]) && time() < self::$tokens[$scope]['expiry'] - 60) {
            return self::$tokens[$scope]['token'];
        }

        $creds = self::getCredentials();

        $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $now = time();
        $claims = base64_encode(json_encode([
            'iss' => $creds['client_email'],
            'scope' => $scope,
            'aud' => self::TOKEN_URL,
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $signatureInput = $header . '.' . $claims;
        $signature = '';
        $privateKey = openssl_pkey_get_private($creds['private_key']);
        if (!$privateKey) {
            throw new \Exception('Invalid private key in service account credentials');
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
        $curlErr = curl_error($ch);

        if ($curlErr) {
            throw new \Exception("Token cURL error: {$curlErr}");
        }
        if ($httpCode !== 200) {
            throw new \Exception("Token exchange failed ({$httpCode}): " . mb_substr($response, 0, 300));
        }

        $tokenData = json_decode($response, true);
        if (!isset($tokenData['access_token'])) {
            throw new \Exception('No access_token in token response');
        }

        self::$tokens[$scope] = [
            'token'  => $tokenData['access_token'],
            'expiry' => $now + ($tokenData['expires_in'] ?? 3600),
        ];

        return self::$tokens[$scope]['token'];
    }

    /* ══════════════════════════════════════════════════════════════
       GEMINI AI — Multimodal Document Reading & Field Extraction
       Sends the image directly to Gemini — no separate OCR needed.
       ══════════════════════════════════════════════════════════════ */

    /**
     * Read a document image directly with Gemini and extract structured fields.
     *
     * This is the PRIMARY extraction method — Gemini sees the image itself,
     * reads Arabic text with full contextual understanding, and returns
     * structured JSON. Far more accurate than OCR + regex for Arabic names
     * and document-specific layouts.
     *
     * @param string $imagePath  Absolute path to image file
     * @return array|null  Extracted fields or null on failure
     */
    public static function extractFromImage(string $imagePath): ?array
    {
        $startTime = microtime(true);

        try {
            if (!file_exists($imagePath)) {
                throw new \Exception("Image file not found: {$imagePath}");
            }

            $imageData = base64_encode(file_get_contents($imagePath));
            $ext = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
            $mimeMap = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'pdf' => 'application/pdf'];
            $mimeType = $mimeMap[$ext] ?? 'image/jpeg';

            $token = self::getTokenForScope(self::SCOPE_GEMINI);
            $prompt = self::buildImageExtractionPrompt();

            $requestParts = [
                ['inline_data' => ['mime_type' => $mimeType, 'data' => $imageData]],
                ['text' => $prompt],
            ];

            $fields = self::callGeminiWithFallback($token, $requestParts);

            $elapsed = (int)((microtime(true) - $startTime) * 1000);
            self::trackUsage('GEMINI_VISION', 'success', $elapsed, null, null, null);
            return $fields;

        } catch (\Throwable $e) {
            $elapsed = (int)((microtime(true) - $startTime) * 1000);
            $errMsg = 'Gemini image extraction failed: ' . $e->getMessage();
            Yii::warning($errMsg, 'vision');
            @file_put_contents(
                Yii::getAlias('@runtime') . '/gemini_debug.log',
                date('[Y-m-d H:i:s] ') . $errMsg . "\n",
                FILE_APPEND
            );
            self::trackUsage('GEMINI_VISION', 'error', $elapsed, null, null, null, $e->getMessage());
            return null;
        }
    }

    /**
     * Legacy: Extract from OCR text using Gemini (fallback when image method unavailable).
     */
    public static function extractWithGemini(string $ocrText, ?array $classification = null): ?array
    {
        $startTime = microtime(true);

        try {
            $token = self::getTokenForScope(self::SCOPE_GEMINI);
            $prompt = self::buildTextExtractionPrompt($ocrText, $classification);

            $fields = self::callGeminiWithFallback($token, [['text' => $prompt]]);

            self::trackUsage('GEMINI_EXTRACT', 'success', (int)((microtime(true) - $startTime) * 1000), null, null, null);
            return $fields;

        } catch (\Throwable $e) {
            $elapsed = (int)((microtime(true) - $startTime) * 1000);
            Yii::warning('Gemini text extraction failed: ' . $e->getMessage(), 'vision');
            self::trackUsage('GEMINI_EXTRACT', 'error', $elapsed, null, null, null, $e->getMessage());
            return null;
        }
    }

    /**
     * Call Gemini API with model fallback chain and retry logic.
     * Tries each model in GEMINI_MODELS, retrying on 503/429.
     *
     * @param string $token OAuth access token
     * @param array $parts  Content parts (text and/or inline_data)
     * @return array Parsed JSON fields
     * @throws \Exception if all models and retries fail
     */
    private static function callGeminiWithFallback(string $token, array $parts): array
    {
        $lastError = '';

        foreach (self::GEMINI_MODELS as $model) {
            $endpoint = sprintf(self::GEMINI_ENDPOINT, $model);

            $requestBody = json_encode([
                'contents' => [['role' => 'user', 'parts' => $parts]],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'topP' => 0.8,
                    'maxOutputTokens' => 1024,
                    'responseMimeType' => 'application/json',
                ],
            ], JSON_UNESCAPED_UNICODE);

            for ($attempt = 0; $attempt < 2; $attempt++) {
                if ($attempt > 0) {
                    usleep(3000000); // 3s backoff before retry
                }

                $ch = curl_init($endpoint);
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

                if ($curlError) {
                    $lastError = "cURL error ({$model}): {$curlError}";
                    break; // cURL errors won't resolve with retry
                }

                if ($httpCode === 200) {
                    return self::parseGeminiResponse($response);
                }

                if ($httpCode === 404) {
                    $lastError = "Model {$model} not available";
                    break; // try next model
                }

                // 503/429 — retry same model
                $lastError = "Model {$model} HTTP {$httpCode}";
            }
        }

        throw new \Exception("All Gemini models failed: {$lastError}");
    }

    /**
     * Parse Gemini response — handles thinking parts in 2.5 models.
     * Skips "thought" parts and extracts JSON from the last content part.
     */
    private static function parseGeminiResponse(string $rawResponse): array
    {
        $data = json_decode($rawResponse, true);
        $parts = $data['candidates'][0]['content']['parts'] ?? [];

        // Find the last non-thought text part
        $textPart = null;
        foreach ($parts as $part) {
            if (!empty($part['thought'])) continue;
            if (isset($part['text'])) {
                $textPart = $part['text'];
            }
        }

        if (!$textPart) {
            throw new \Exception('Gemini returned no content (parts: ' . count($parts) . ')');
        }

        $fields = json_decode($textPart, true);
        if (!is_array($fields)) {
            if (preg_match('/```(?:json)?\s*(\{[\s\S]*?\})\s*```/', $textPart, $jm)) {
                $fields = json_decode($jm[1], true);
            }
            if (!is_array($fields)) {
                throw new \Exception('Gemini response is not valid JSON: ' . mb_substr($textPart, 0, 200));
            }
        }

        return $fields;
    }

    /**
     * Build prompt for direct image reading (primary method).
     */
    private static function buildImageExtractionPrompt(): string
    {
        return <<<'PROMPT'
أنت محلل وثائق متخصص في الوثائق الأردنية والعربية. اقرأ هذه الصورة واستخرج جميع البيانات الشخصية الظاهرة.

القواعد المهمة:
- اقرأ النص العربي بدقة عالية — انتبه للأسماء جيداً (مثلاً "إسلام" وليس "السلام"، "حسنا" وليس "حسبنا")
- أرجع فقط الحقول الموجودة فعلاً في الصورة — لا تخترع بيانات أبداً
- إذا ظهرت MRZ (سطور بأحرف لاتينية كبيرة و < في أسفل الوثيقة)، استخدمها أيضاً لتأكيد البيانات
- التواريخ بصيغة YYYY-MM-DD
- الجنس: 0 = ذكر، 1 = أنثى
- نوع الوثيقة: 0=هوية/بطاقة شخصية، 1=جواز سفر، 2=رخصة قيادة، 3=شهادة ميلاد، 4=شهادة تعيين عسكرية
- الجهة المصدرة (للوثائق العسكرية فقط): army=القوات المسلحة، security=الأمن العام، intelligence=المخابرات العامة
- الرقم الوطني الأردني = 10 أرقام بالضبط
- رقم الوثيقة (بطاقة/جواز) = عادة حروف لاتينية وأرقام

أرجع JSON فقط بالحقول المتوفرة:
{
  "name": "الاسم الرباعي الكامل بالعربي كما هو مكتوب",
  "name_en": "Full name in English if visible",
  "id_number": "الرقم الوطني 10 أرقام",
  "sex": 0,
  "birth_date": "YYYY-MM-DD",
  "birth_place": "مكان الولادة",
  "mother_name": "اسم الأم الكامل",
  "nationality_text": "الجنسية",
  "document_type": "0",
  "document_number": "رقم البطاقة/الوثيقة",
  "military_number": "الرقم العسكري أو الوظيفي",
  "rank": "الرتبة العسكرية",
  "issuing_body": "army أو security أو intelligence",
  "certificate_number": "رقم الشهادة",
  "address": "العنوان الكامل"
}
PROMPT;
    }

    /**
     * Build prompt for OCR text extraction (fallback method).
     */
    private static function buildTextExtractionPrompt(string $ocrText, ?array $classification = null): string
    {
        $docHint = '';
        if ($classification && !empty($classification['label'])) {
            $docHint = "\nDocument type hint: {$classification['label']}";
        }

        return <<<PROMPT
أنت محلل وثائق متخصص في الوثائق الأردنية. النص التالي مستخرج بالـ OCR من وثيقة رسمية.
استخرج البيانات المتوفرة وأرجعها كـ JSON فقط.{$docHint}

القواعد:
- أرجع فقط الحقول الموجودة فعلاً في النص — لا تخترع بيانات
- الأسماء العربية: صحّح أخطاء OCR الشائعة (مثل "السلام" → "إسلام" إذا كان اسم شخص)
- التواريخ بصيغة YYYY-MM-DD
- الجنس: 0 = ذكر، 1 = أنثى
- نوع الوثيقة: 0=هوية، 1=جواز سفر، 2=رخصة قيادة، 3=شهادة ميلاد، 4=شهادة تعيين
- الجهة المصدرة: army=قوات مسلحة، security=أمن عام، intelligence=مخابرات عامة
- الرقم الوطني الأردني 10 أرقام
- رقم الوثيقة (البطاقة/الجواز) عادة حروف وأرقام

حقول JSON المطلوبة (فقط الموجودة):
{
  "name": "الاسم الكامل بالعربي",
  "name_en": "Full name in English",
  "id_number": "الرقم الوطني 10 أرقام",
  "sex": 0,
  "birth_date": "YYYY-MM-DD",
  "birth_place": "مكان الولادة",
  "mother_name": "اسم الأم",
  "nationality_text": "الجنسية بالعربي",
  "document_type": "0",
  "document_number": "رقم الوثيقة",
  "military_number": "الرقم العسكري/الوظيفي",
  "rank": "الرتبة",
  "issuing_body": "army أو security أو intelligence",
  "certificate_number": "رقم الشهادة",
  "address": "العنوان"
}

نص الـ OCR:
---
{$ocrText}
---
PROMPT;
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

    // ═══════════════════════════════════════════════════════════
    // DOCUMENT FIELD EXTRACTION ENGINE
    // 3-layer approach: MRZ → Structured Arabic → Regex fallback
    // ═══════════════════════════════════════════════════════════

    /**
     * Customer document type mapping (matches CustomersDocument dropdown)
     */
    const DOC_TYPE_ID        = '0'; // هوية
    const DOC_TYPE_PASSPORT  = '1'; // جواز سفر
    const DOC_TYPE_LICENSE   = '2'; // رخصة قيادة
    const DOC_TYPE_BIRTH     = '3'; // شهادة ميلاد
    const DOC_TYPE_MILITARY  = '4'; // شهادة تعيين

    /**
     * Issuing body detection keywords
     */
    private static $issuingBodies = [
        'army' => [
            'keywords' => ['القوات المسلحة', 'الجيش العربي', 'مديرية شؤون الأفراد', 'JORDAN ARMED FORCES', 'ARAB ARMY', 'IDJAF'],
            'job_name' => 'القوات المسلحة الأردنية - الجيش العربي',
        ],
        'security' => [
            'keywords' => ['الأمن العام', 'مديرية الأمن العام', 'PUBLIC SECURITY', 'IDPSD'],
            'job_name' => 'الأمن العام',
        ],
        'intelligence' => [
            'keywords' => ['المخابرات العامة', 'مدير المخابرات'],
            'job_name' => 'المخابرات العامة',
        ],
        'gendarmerie' => [
            'keywords' => ['الدرك', 'قوات الدرك'],
            'job_name' => 'قوات الدرك',
        ],
        'civil_defense' => [
            'keywords' => ['الدفاع المدني'],
            'job_name' => 'الدفاع المدني',
        ],
    ];

    /**
     * Smart document extraction — sends image directly to Gemini first,
     * falls back to Vision OCR + regex if Gemini unavailable.
     *
     * @param string $imagePath  Absolute path to image file
     * @return array ['customer' => [...], 'document' => [...], 'job' => [...], 'meta' => [...]]
     */
    public static function extractFromDocument(string $imagePath): array
    {
        $result = self::buildEmptyResult();
        $typeLabels = self::getTypeLabels();

        // Read Gemini debug log for last error (if any)
        $debugLog = Yii::getAlias('@runtime') . '/gemini_debug.log';

        // ═══ PRIMARY: Gemini reads the image directly ═══
        $geminiFields = self::extractFromImage($imagePath);

        if ($geminiFields && is_array($geminiFields)) {
            $result['meta']['source'] = 'gemini-vision';
            self::mergeFields($result, $geminiFields);
            self::applyIssuingBody($result, $geminiFields);

            if (isset($result['document']['type'])) {
                $result['document']['type_label'] = $typeLabels[$result['document']['type']] ?? '';
            }
            $result['meta']['confidence_notes'][] = 'Gemini Vision — قراءة مباشرة من الصورة';
        }

        // ═══ FALLBACK: Vision OCR + Gemini text + regex ═══
        $needsFallback = !$geminiFields
            || empty($result['customer']['name'])
            || empty($result['customer']['id_number']);

        if ($needsFallback) {
            $lastError = '';
            if (file_exists($debugLog)) {
                $lines = file($debugLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $lastError = end($lines) ?: '';
            }

            if (!$geminiFields) {
                $note = 'Gemini Vision غير متاح — fallback to OCR';
                if ($lastError) {
                    $note .= ' | ' . $lastError;
                }
                $result['meta']['confidence_notes'][] = $note;
            }

            $ocrResult = self::analyze($imagePath, ['DOCUMENT_TEXT_DETECTION', 'LABEL_DETECTION']);

            if ($ocrResult['success'] && !empty($ocrResult['text'])) {
                $result['meta']['ocr_text'] = $ocrResult['text'];
                $result['meta']['classification'] = $ocrResult['classification'] ?? null;

                $ocrExtraction = self::extractDocumentFields(
                    $ocrResult['text'],
                    $ocrResult['labels'] ?? [],
                    $ocrResult['classification'] ?? null
                );

                // Merge OCR results only for missing fields
                foreach (['customer', 'document', 'job'] as $section) {
                    foreach ($ocrExtraction[$section] ?? [] as $key => $val) {
                        if (!empty($val) && empty($result[$section][$key])) {
                            $result[$section][$key] = $val;
                        }
                    }
                }

                if (!$geminiFields) {
                    $result['meta']['source'] = $ocrExtraction['meta']['source'] ?? 'ocr+regex';
                }
            }
        }

        self::finalizeResult($result, null, null);
        return $result;
    }

    /**
     * Extract structured fields from OCR text.
     * Returns customer-model-ready fields + document info.
     *
     * @param string $ocrText  Raw text from Vision OCR
     * @param array  $labels   Vision API label annotations
     * @param array|null $classification  Document classification from classifyDocument()
     * @return array ['customer' => [...], 'document' => [...], 'job' => [...], 'meta' => [...]]
     */
    public static function extractDocumentFields(string $ocrText, array $labels = [], ?array $classification = null): array
    {
        $result = self::buildEmptyResult();
        $typeLabels = self::getTypeLabels();

        // ═══ PRIMARY: Gemini AI text extraction ═══
        $geminiFields = self::extractWithGemini($ocrText, $classification);

        if ($geminiFields && is_array($geminiFields)) {
            $result['meta']['source'] = 'gemini-text';
            self::mergeFields($result, $geminiFields);
            self::applyIssuingBody($result, $geminiFields);

            if (isset($result['document']['type'])) {
                $result['document']['type_label'] = $typeLabels[$result['document']['type']] ?? '';
            }
            $result['meta']['confidence_notes'][] = 'Gemini AI text extraction';
        }

        // ═══ FALLBACK: Regex extraction (if Gemini failed or missed fields) ═══
        $regexNeeded = !$geminiFields
            || empty($result['customer']['name'])
            || empty($result['customer']['id_number']);

        if ($regexNeeded) {
            if (!$geminiFields) {
                $result['meta']['confidence_notes'][] = 'Gemini unavailable — regex fallback';
            } else {
                $result['meta']['confidence_notes'][] = 'Regex supplementing Gemini';
            }

            $mrzFields = self::parseMRZ($ocrText);
            if ($mrzFields) {
                $result['meta']['mrz_found'] = true;
                if (!$geminiFields) $result['meta']['source'] = 'mrz+regex';
                self::mergeFields($result, $mrzFields, false);
            } elseif (!$geminiFields) {
                $result['meta']['source'] = 'regex';
            }

            $textFields = self::parseStructuredText($ocrText);
            self::mergeFields($result, $textFields, false);

            if (empty($result['meta']['issuing_body'])) {
                $body = self::detectIssuingBody($ocrText);
                if ($body) {
                    $result['meta']['issuing_body'] = $body;
                    if (empty($result['job']['employer_name'])) {
                        $result['job']['employer_name'] = self::$issuingBodies[$body]['job_name'];
                    }
                }
            }
        }

        self::finalizeResult($result, $ocrText, $classification);
        return $result;
    }

    // ═══ Shared helpers ═══

    private static function buildEmptyResult(): array
    {
        return [
            'customer' => [],
            'document' => [],
            'job' => [],
            'meta' => [
                'source' => 'unknown',
                'issuing_body' => null,
                'mrz_found' => false,
                'fields_extracted' => 0,
                'confidence_notes' => [],
            ],
        ];
    }

    private static function getTypeLabels(): array
    {
        return [
            '0' => 'هوية', '1' => 'جواز سفر', '2' => 'رخصة قيادة',
            '3' => 'شهادة ميلاد', '4' => 'شهادة تعيين',
        ];
    }

    private static function applyIssuingBody(array &$result, array $fields): void
    {
        if (!empty($fields['issuing_body'])) {
            $bodyKey = $fields['issuing_body'];
            if (isset(self::$issuingBodies[$bodyKey])) {
                $result['meta']['issuing_body'] = $bodyKey;
                $result['job']['employer_name'] = self::$issuingBodies[$bodyKey]['job_name'];
            }
        }
    }

    private static function finalizeResult(array &$result, ?string $ocrText = null, ?array $classification = null): void
    {
        $typeLabels = self::getTypeLabels();

        if (!isset($result['document']['type'])) {
            if (!empty($result['meta']['issuing_body'])) {
                $result['document']['type'] = self::DOC_TYPE_MILITARY;
            } elseif ($ocrText) {
                $result['document']['type'] = self::detectDocumentType($ocrText, $classification);
            } else {
                $result['document']['type'] = 0;
            }
            $result['document']['type_label'] = $typeLabels[$result['document']['type']] ?? '';
        }

        $count = 0;
        foreach (['customer', 'document', 'job'] as $group) {
            $count += count(array_filter($result[$group]));
        }
        $result['meta']['fields_extracted'] = $count;
    }

    /**
     * Parse MRZ (Machine Readable Zone) — 3-line format on Jordanian IDs.
     *
     * Formats detected from real documents:
     *   Civil:    IDJOR[card]<[nat_no]     / [YYMMDD][sex][expiry]JOR / [FAMILY]<<[NAMES]
     *   Army:     IDJAF[card]<[check]<<<<[nat_no] / [YYMMDD][sex]...JOR<<<<[mil_no] / [FAMILY]<<[NAMES]
     *   Security: IDPSD[series][card]<[check]<<<<[nat_no] / ... / [FAMILY]<<[NAMES]
     */
    private static function parseMRZ(string $text): ?array
    {
        // Find MRZ lines: sequences of uppercase + digits + < characters, min 25 chars
        // Handle common OCR noise: spaces within MRZ, misread < as « » ‹ ›, etc.
        $ocrReplacements = ['«' => '<', '»' => '<', '‹' => '<', '›' => '<', '＜' => '<', '≪' => '<', 'く' => '<', 'К' => 'K'];
        $lines = [];
        foreach (preg_split('/[\r\n]+/', $text) as $line) {
            $clean = trim($line);
            // Strip spaces from potential MRZ lines
            $stripped = preg_replace('/\s+/', '', $clean);
            // Replace common OCR misreads of < character
            $stripped = strtr($stripped, $ocrReplacements);
            if (preg_match('/^[A-Z0-9<\/]{25,}$/', $stripped)) {
                $lines[] = $stripped;
            }
        }

        // Also try to reconstruct MRZ from lines that are mostly MRZ-like (>80% valid chars)
        if (count($lines) < 3) {
            foreach (preg_split('/[\r\n]+/', $text) as $line) {
                $clean = trim($line);
                if (strlen($clean) < 20) continue;
                $stripped = preg_replace('/\s+/', '', $clean);
                $stripped = strtr($stripped, $ocrReplacements);
                $validChars = preg_replace('/[^A-Z0-9<\/]/', '', $stripped);
                if (strlen($validChars) >= 25 && strlen($validChars) / max(strlen($stripped), 1) > 0.80) {
                    $lines[] = $validChars;
                }
            }
        }

        if (count($lines) < 3) return null;

        // Take last 3 MRZ-like lines
        $mrzLines = array_slice($lines, -3);
        $line1 = $mrzLines[0];
        $line2 = $mrzLines[1];
        $line3 = $mrzLines[2];

        $fields = [];

        // Line 1: Document type + national number
        // IDJOR...<...9952044806<<<<<
        // IDJAF00212449<4<<<<9881056352
        // IDPSD0B097368<6<<<<9881041371
        if (preg_match('/^ID([A-Z]{3})/', $line1, $m)) {
            $orgCode = $m[1];
            $fields['meta_org'] = $orgCode;

            if ($orgCode === 'JOR') {
                $fields['document_type'] = self::DOC_TYPE_ID;
            } else {
                $fields['document_type'] = self::DOC_TYPE_MILITARY;
            }
        }

        // Extract national number (10 digits) from line 1
        if (preg_match('/(\d{10})/', $line1, $m)) {
            $fields['id_number'] = $m[1];
        }

        // Extract card/document number from line 1 (between ID prefix and first <)
        if (preg_match('/^ID[A-Z]{3}0*([A-Z0-9]+?)</', $line1, $m)) {
            $fields['document_number'] = $m[1];
        }

        // Line 2: Birth date, sex, expiry, nationality, military number
        // 9512128F3503177JOR...
        // 8810056M250/205JOR<<<<443769<4
        if (preg_match('/^(\d{6})(\d)([MF])/', $line2, $m)) {
            $yy = substr($m[1], 0, 2);
            $mm = substr($m[1], 2, 2);
            $dd = substr($m[1], 4, 2);
            $century = ((int)$yy > 50) ? '19' : '20';
            $fields['birth_date'] = $century . $yy . '-' . $mm . '-' . $dd;
            $fields['sex'] = ($m[3] === 'F') ? 1 : 0;
        }

        // Extract military number from line 2 (after JOR<<<<)
        if (preg_match('/JOR<{2,}(\d{4,})</', $line2, $m)) {
            $fields['military_number'] = $m[1];
        }

        // Line 3: Name — FAMILY<<FIRST<FATHER<GRANDFATHER
        if (preg_match('/^([A-Z]+)<<([A-Z<]+)/', $line3, $m)) {
            $family = $m[1];
            $givenParts = array_filter(explode('<', $m[2]));
            $fullParts = array_merge($givenParts, [$family]);
            $fields['name_en'] = implode(' ', array_map('ucfirst', array_map('strtolower', $fullParts)));

            if (count($givenParts) >= 1) $fields['first_name_en'] = ucfirst(strtolower($givenParts[0]));
            $fields['family_name_en'] = ucfirst(strtolower($family));
        }

        // Nationality from line 2
        if (mb_strpos($line2, 'JOR') !== false) {
            $fields['nationality'] = 'JOR';
        }

        return $fields;
    }

    /**
     * Parse structured Arabic + English text fields from document OCR.
     *
     * Uses a flat (single-line) version of the OCR text for regex matching so
     * that label/value pairs split across lines are still captured.  Known
     * subsequent labels are trimmed from captured values to prevent over-matching.
     */
    private static function parseStructuredText(string $text): array
    {
        $fields = [];

        // Build two views of the text:
        //   $lines — array, preserving line breaks for line-by-line fallback
        //   $flat  — single line, tabs/newlines collapsed to spaces
        $text  = str_replace("\r\n", "\n", $text);
        $text  = str_replace("\r", "\n", $text);
        $lines = array_values(array_filter(
            array_map('trim', explode("\n", $text)),
            function ($l) { return $l !== ''; }
        ));
        $flat = preg_replace('/\s+/u', ' ', implode(' ', $lines));

        // Labels that signal "stop capturing" — used to trim greedy captures
        $stopLabels = 'الرقم|الجنس|تاريخ|مكان|اسم\s*الا|رقم|السجل|الجنسي|Name|National|Sex|Gender|Date|Birth|Place|No\.';

        // ── Arabic Name ──
        // Handle all alef variants: ا إ أ آ (different Unicode codepoints)
        // Alef is REQUIRED — without it, "الم" falsely matches المملكة/المدنية/etc.
        $alefLabel = 'ال[اإأآ]سـ*م';

        // Strategy 1: "الاسم" then skip non-Arabic (e.g. "Name") → capture Arabic text
        if (preg_match('/(?:' . $alefLabel . ')[^\p{Arabic}]*([\p{Arabic}][\p{Arabic}\s]{3,})/u', $flat, $m)) {
            $name = self::cleanArabicCapture($m[1], $stopLabels);
            if (mb_strlen($name) >= 4) {
                $fields['name'] = $name;
            }
        }
        // Strategy 2: bare "اسم" without ال (some OCR strips the definite article)
        if (!isset($fields['name']) && preg_match('/(?:^|\s)[اإأ]سـ*م\s*[:\-.]?\s*([\p{Arabic}][\p{Arabic}\s]{3,})/u', $flat, $m)) {
            $name = self::cleanArabicCapture($m[1], $stopLabels);
            if (mb_strlen($name) >= 4) {
                $fields['name'] = $name;
            }
        }
        // Strategy 3: label on its own line (possibly with "Name"), value on next line
        if (!isset($fields['name'])) {
            foreach ($lines as $i => $line) {
                if (preg_match('/' . $alefLabel . '/u', $line) && isset($lines[$i + 1])) {
                    $rest = preg_replace('/' . $alefLabel . '/u', '', $line);
                    // Line has label but no substantial Arabic content after it
                    if (!preg_match('/[\p{Arabic}]{4,}/u', $rest)) {
                        $candidate = self::cleanArabicCapture($lines[$i + 1], $stopLabels);
                        if (mb_strlen($candidate) >= 4) {
                            $fields['name'] = $candidate;
                            break;
                        }
                    }
                }
            }
        }
        // Strategy 4: Arabic text immediately before English name
        if (!isset($fields['name'])) {
            if (preg_match('/([\p{Arabic}][\p{Arabic}\s]{5,})\s+[A-Z]{2,}\s+[A-Z]/u', $flat, $m)) {
                $candidate = self::cleanArabicCapture($m[1], $stopLabels);
                // Exclude header/title-like text
                if (mb_strlen($candidate) >= 6
                    && !preg_match('/(?:المملكة|الأردنية|الهاشمية|وزارة|الداخلية|بطاقة|شخصية|دائرة|الاحوال)/u', $candidate)
                    && mb_substr_count($candidate, ' ') >= 1
                ) {
                    $fields['name'] = $candidate;
                }
            }
        }
        // Strategy 5: find longest Arabic phrase (3+ words) not matching known headers
        if (!isset($fields['name'])) {
            $headerWords = '/(?:المملكة|الأردنية|الهاشمية|الداخلية|وزارة|بطاقة|شخصية|دائرة|الاحوال|المدنية|الجوازات|شهادة|تعيين)/u';
            preg_match_all('/([\p{Arabic}]+(?:\s+[\p{Arabic}]+){2,})/u', $flat, $allMatches);
            $bestName = '';
            foreach ($allMatches[1] ?? [] as $candidate) {
                $cleaned = self::cleanArabicCapture($candidate, $stopLabels);
                if (preg_match($headerWords, $cleaned)) continue;
                if (mb_strlen($cleaned) > mb_strlen($bestName) && mb_strlen($cleaned) >= 8) {
                    $bestName = $cleaned;
                }
            }
            if ($bestName) {
                $fields['name'] = $bestName;
            }
        }

        // English name ("Name: ISLAM RASHED ...")
        if (preg_match('/\bName\s*[:\-.]?\s*([A-Z][A-Z\s]{3,})/i', $flat, $m)) {
            $nameEn = trim(preg_replace('/\s+/', ' ', $m[1]));
            $nameEn = preg_replace('/\s*(?:National|Sex|Gender|Date|Birth|Place|No\.|ID|الرقم|الجنس).*$/iu', '', $nameEn);
            if (strlen(trim($nameEn)) >= 4) {
                $fields['name_en'] = trim($nameEn);
            }
        }

        // ── National Number (10 digits) ──
        if (preg_match('/(?:الرقم\s*الوطني|National\s*No\.?|NAT[\-\s]*NO)\s*[:\-.]?\s*(\d{10})/iu', $flat, $m)) {
            $fields['id_number'] = $m[1];
        }
        // Fallback: if exactly one 10-digit number exists in the text
        if (!isset($fields['id_number'])) {
            preg_match_all('/\b(\d{10})\b/', $flat, $allNums);
            if (count($allNums[1]) === 1) {
                $fields['id_number'] = $allNums[1][0];
            }
        }

        // ── Sex ──
        // Variants: أنثى, انثى, ثنى, انتى (OCR misread ث→ت)
        $femaleWords = 'أنثى|انثى|ثنى|انتى|أنتى';
        if (preg_match('/(?:الجنس|Sex|Gender)\s*[:\-.]?\s*(ذكر|' . $femaleWords . '|Male|Female|M|F)\b/iu', $flat, $m)) {
            $val = mb_strtolower(trim($m[1]));
            $fields['sex'] = in_array($val, ['أنثى', 'انثى', 'ثنى', 'انتى', 'أنتى', 'female', 'f']) ? 1 : 0;
        }
        // Fallback: Arabic gender word followed by /M or /F
        if (!isset($fields['sex'])) {
            if (preg_match('/(?:ذكر|' . $femaleWords . ')[\s\/]*(M|F)/iu', $flat, $m)) {
                $fields['sex'] = (strtoupper($m[1]) === 'F') ? 1 : 0;
            } elseif (preg_match('/\b(M|F)\s*[\/%]\s*(?:ذكر|' . $femaleWords . ')/iu', $flat, $m)) {
                $fields['sex'] = (strtoupper($m[1]) === 'F') ? 1 : 0;
            }
        }

        // ── Birth Date ──
        $dateRx = '(\d{1,4}[\/-]\d{1,2}[\/-]\d{1,4})';
        $datePatterns = [
            '/(?:تاريخ\s*(?:الولاد[ةه]|الميلاد)|D\.?O\.?B\.?|Date\s*of\s*Birth|BIR[\-\s]*DATE)\s*[:\-.]?\s*' . $dateRx . '/iu',
            '/(?:مكان\s*وتاريخ\s*الولاد[ةه])\s*[:\-.]?\s*\S+\s+' . $dateRx . '/iu',
        ];
        foreach ($datePatterns as $p) {
            if (preg_match($p, $flat, $m)) {
                $fields['birth_date'] = self::normalizeDateToISO($m[1]);
                break;
            }
        }
        // Line-by-line fallback for birth date
        if (!isset($fields['birth_date'])) {
            foreach ($lines as $i => $line) {
                if (preg_match('/تاريخ\s*(?:الولاد[ةه]|الميلاد)/u', $line)) {
                    $searchIn = $line . ' ' . ($lines[$i + 1] ?? '');
                    if (preg_match('/' . $dateRx . '/', $searchIn, $dm)) {
                        $fields['birth_date'] = self::normalizeDateToISO($dm[1]);
                        break;
                    }
                }
            }
        }

        // ── Birth Place ──
        $placePatterns = [
            '/(?:مكان\s*(?:الولاد[ةه]|الميلاد)|Place\s*(?:of\s*)?Birth)\s*[:\-.]?\s*([^\d\n,]{2,30})/iu',
            '/مكان\s*وتاريخ\s*الولاد[ةه]\s*[:\-.]?\s*([\p{Arabic}]+)/u',
        ];
        foreach ($placePatterns as $p) {
            if (preg_match($p, $flat, $m)) {
                $place = trim(preg_replace('/\s+/u', ' ', $m[1]));
                $place = preg_replace('/\s*(?:' . $stopLabels . ').*$/iu', '', $place);
                if (mb_strlen(trim($place)) >= 2 && !preg_match('/^\d/', $place)) {
                    $fields['birth_place'] = trim($place);
                    break;
                }
            }
        }

        // ── Nationality ──
        if (preg_match('/(?:الجنسي[ةه]|Nationality)\s*[:\-.]?\s*([^\d\n]{2,20})/iu', $flat, $m)) {
            $nat = trim($m[1]);
            $nat = preg_replace('/\s*(?:' . $stopLabels . ').*$/iu', '', $nat);
            if (mb_strlen(trim($nat)) >= 2) {
                $fields['nationality_text'] = trim($nat);
            }
        }

        // ── Mother's Name ──
        if (preg_match('/(?:اسم\s*الا[مأ]|Mother)\s*[:\-.]?\s*([\p{Arabic}\s]{2,})/iu', $flat, $m)) {
            $name = self::cleanArabicCapture($m[1], $stopLabels);
            if (mb_strlen($name) >= 2) {
                $fields['mother_name'] = $name;
            }
        }
        // Line-by-line fallback
        if (!isset($fields['mother_name'])) {
            foreach ($lines as $i => $line) {
                if (preg_match('/اسم\s*الا[مأ]/u', $line) && isset($lines[$i + 1])) {
                    $candidate = self::cleanArabicCapture($lines[$i + 1], $stopLabels);
                    if (mb_strlen($candidate) >= 2) {
                        $fields['mother_name'] = $candidate;
                        break;
                    }
                }
            }
        }

        // ── Military / Service Number ──
        if (preg_match('/(?:الرقم\s*العسكري|MIL[\-\s]*NO\.?)\s*[:\-.]?\s*(\d{4,})/iu', $flat, $m)) {
            $fields['military_number'] = $m[1];
        }
        // Intelligence: "الرقم 242864" or "الرقـم: 242864"
        if (!isset($fields['military_number']) && preg_match('/(?:الرقـ*م)\s*[:\-.]?\s*(\d{4,})/u', $flat, $m)) {
            $nat = $fields['id_number'] ?? '';
            if ($m[1] !== $nat) {
                $fields['military_number'] = $m[1];
            }
        }

        // ── Rank ──
        if (preg_match('/(?:الرتب[ةه]|RANK)\s*[:\-.]?\s*(.+)/iu', $flat, $m)) {
            $rank = trim(preg_replace('/\s+/u', ' ', $m[1]));
            $rank = preg_replace('/\s*(?:' . $stopLabels . ').*$/iu', '', $rank);
            if (mb_strlen(trim($rank)) >= 2 && mb_strlen(trim($rank)) <= 40) {
                $fields['rank'] = trim($rank);
            }
        }

        // ── Document-specific numbers ──

        // ID card number: "رقم الهوية QQA62485" or "ID No FBY86966" or "Card No: ..."
        if (preg_match('/(?:رقم\s*(?:ال)?هوي[ةه]|رقم\s*(?:ال)?بطاق[ةه]|ID\s*(?:Card\s*)?No\.?|Card\s*No\.?)\s*[:\-.]?\s*([A-Z0-9]{5,})/iu', $flat, $m)) {
            $fields['document_number'] = strtoupper($m[1]);
            $fields['document_type'] = self::DOC_TYPE_ID;
        }
        // Jordanian ID card pattern: 3 uppercase letters + 5 digits (e.g. FBY86966)
        if (!isset($fields['document_number']) && preg_match('/\b([A-Z]{3}\d{5})\b/', $flat, $m)) {
            $fields['document_number'] = $m[1];
            $fields['document_type'] = self::DOC_TYPE_ID;
        }

        // Passport number
        if (preg_match('/(?:رقم\s*(?:ال)?جواز(?:\s*السفر)?|Passport\s*No\.?)\s*[:\-.]?\s*([A-Z0-9]{5,})/iu', $flat, $m)) {
            $fields['document_number'] = strtoupper($m[1]);
            $fields['document_type'] = self::DOC_TYPE_PASSPORT;
        }

        // License number
        if (preg_match('/(?:رقم\s*(?:ال)?رخص[ةه]|License\s*No\.?|DL\s*No\.?)\s*[:\-.]?\s*(\d{5,})/iu', $flat, $m)) {
            $fields['document_number'] = $m[1];
            $fields['document_type'] = self::DOC_TYPE_LICENSE;
        }

        // Certificate number
        if (preg_match('/رقم\s*الشـ*هـ*اد[ةه]\s*[:\-.]?\s*(\d+)/u', $flat, $m)) {
            $fields['certificate_number'] = $m[1];
            if (!isset($fields['document_number'])) {
                $fields['document_number'] = $m[1];
            }
        }

        // ── Address ──
        if (preg_match('/(?:العنوان|مكان\s*الا[قإ]ام[ةه]|Address)\s*[:\-.]?\s*([^\d\n]{2,40})/iu', $flat, $m)) {
            $addr = trim($m[1]);
            $addr = preg_replace('/\s*(?:' . $stopLabels . ').*$/iu', '', $addr);
            if (mb_strlen(trim($addr)) >= 2) {
                $fields['address'] = trim($addr);
            }
        }

        return $fields;
    }

    /**
     * Clean an Arabic text capture: strip non-Arabic chars, trim at next known label.
     */
    private static function cleanArabicCapture(string $text, string $stopLabels): string
    {
        $text = preg_replace('/[^\p{Arabic}\s]/u', '', $text);
        $text = preg_replace('/\s*(?:' . $stopLabels . ').*$/u', '', $text);
        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * Detect the issuing body from document text.
     * @return string|null  Key from self::$issuingBodies or null
     */
    private static function detectIssuingBody(string $text): ?string
    {
        $textLower = mb_strtolower($text);
        foreach (self::$issuingBodies as $key => $body) {
            foreach ($body['keywords'] as $kw) {
                if (mb_strpos($textLower, mb_strtolower($kw)) !== false) {
                    return $key;
                }
            }
        }
        return null;
    }

    /**
     * Detect document type from text when not determined by MRZ.
     */
    private static function detectDocumentType(string $text, ?array $classification): string
    {
        // Use existing classification if available
        if ($classification && isset($classification['type'])) {
            $t = (string)$classification['type'];
            if (in_array($t, ['0','1','2','3','4'])) return $t;
            if ($t === '7') return self::DOC_TYPE_MILITARY; // military_certificate → شهادة تعيين
        }

        $textLower = mb_strtolower($text);

        if (mb_strpos($textLower, 'بطاقة شخصية') !== false || mb_strpos($textLower, 'id card') !== false) {
            return self::DOC_TYPE_ID;
        }
        if (mb_strpos($textLower, 'جواز') !== false || mb_strpos($textLower, 'passport') !== false) {
            return self::DOC_TYPE_PASSPORT;
        }
        if (mb_strpos($textLower, 'رخصة') !== false || mb_strpos($textLower, 'driving') !== false || mb_strpos($textLower, 'license') !== false) {
            return self::DOC_TYPE_LICENSE;
        }
        if (mb_strpos($textLower, 'شهادة ميلاد') !== false || mb_strpos($textLower, 'birth') !== false) {
            return self::DOC_TYPE_BIRTH;
        }
        if (mb_strpos($textLower, 'شهادة') !== false && (mb_strpos($textLower, 'تعيين') !== false || mb_strpos($textLower, 'عسكري') !== false)) {
            return self::DOC_TYPE_MILITARY;
        }
        if (self::detectIssuingBody($text) !== null) {
            return self::DOC_TYPE_MILITARY;
        }

        return '0'; // default to هوية
    }

    /**
     * Normalize date string to ISO format (YYYY-MM-DD).
     * Handles: DD/MM/YYYY, YYYY/MM/DD, DD-MM-YYYY
     */
    private static function normalizeDateToISO(string $dateStr): string
    {
        $dateStr = trim($dateStr);
        $parts = preg_split('/[\/-]/', $dateStr);
        if (count($parts) !== 3) return $dateStr;

        $a = (int)$parts[0];
        $b = (int)$parts[1];
        $c = (int)$parts[2];

        if ($a > 1900) {
            // YYYY/MM/DD
            return sprintf('%04d-%02d-%02d', $a, $b, $c);
        }
        if ($c > 1900) {
            // DD/MM/YYYY
            return sprintf('%04d-%02d-%02d', $c, $b, $a);
        }

        return $dateStr;
    }

    /**
     * Merge extracted fields into result structure without overwriting existing values.
     */
    private static function mergeFields(array &$result, array $fields, bool $overwrite = true): void
    {
        // Map raw fields to result structure
        $customerMap = [
            'name' => 'name',
            'name_en' => 'name_en',
            'id_number' => 'id_number',
            'birth_date' => 'birth_date',
            'sex' => 'sex',
            'birth_place' => 'birth_place',
            'nationality' => 'nationality',
            'nationality_text' => 'nationality_text',
            'mother_name' => 'mother_name',
            'address' => 'address',
        ];

        $docMap = [
            'document_type' => 'type',
            'document_number' => 'number',
            'certificate_number' => 'certificate_number',
        ];

        $jobMap = [
            'military_number' => 'job_number',
            'rank' => 'rank',
        ];

        foreach ($customerMap as $src => $dst) {
            if (isset($fields[$src]) && ($overwrite || !isset($result['customer'][$dst]) || $result['customer'][$dst] === '')) {
                $result['customer'][$dst] = $fields[$src];
            }
        }

        foreach ($docMap as $src => $dst) {
            if (isset($fields[$src]) && ($overwrite || !isset($result['document'][$dst]) || $result['document'][$dst] === '')) {
                $result['document'][$dst] = $fields[$src];
            }
        }

        foreach ($jobMap as $src => $dst) {
            if (isset($fields[$src]) && ($overwrite || !isset($result['job'][$dst]) || $result['job'][$dst] === '')) {
                $result['job'][$dst] = $fields[$src];
            }
        }

        // MRZ org code → meta
        if (isset($fields['meta_org'])) {
            $result['meta']['mrz_org'] = $fields['meta_org'];
        }
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
