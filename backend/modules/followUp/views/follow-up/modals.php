<?php
/**
 * نوافذ منبثقة لشاشة المتابعة - بناء من الصفر
 * تشمل: إرسال رسالة، تغيير حالة العقد، صور العملاء، بيانات العميل، التدقيق، التسوية
 */
use yii\helpers\Html;
use yii\helpers\Url;
use backend\modules\contracts\models\Contracts;

$contractModel = $contractCalculations->contract_model;
?>

<!-- ═══ CSS مشترك — شريط المسودات والمتغيرات ═══ -->
<style>
.sdt-toolbar{display:flex;gap:4px;flex-wrap:wrap;margin:6px 0}
.sdt-btn{border:none;border-radius:8px;padding:6px 12px;font-size:11px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:5px;transition:all .15s;font-family:inherit}
.sdt-btn-vars{background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff}
.sdt-btn-vars:hover{box-shadow:0 2px 10px rgba(99,102,241,.35)}
.sdt-btn-drafts{background:linear-gradient(135deg,#F59E0B,#D97706);color:#fff}
.sdt-btn-drafts:hover{box-shadow:0 2px 10px rgba(245,158,11,.35)}
.sdt-btn-save{background:linear-gradient(135deg,#10B981,#059669);color:#fff}
.sdt-btn-save:hover{box-shadow:0 2px 10px rgba(16,185,129,.35)}

.sdt-panel{display:none;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:10px;margin-top:6px;animation:sdt-fadeIn .15s ease}
.sdt-panel.open{display:block}
.sdt-panel-title{font-size:10px;font-weight:700;color:#64748B;margin-bottom:8px;display:flex;align-items:center;gap:5px;letter-spacing:.3px}

.sdt-vars-grid{display:flex;flex-wrap:wrap;gap:4px}
.sdt-var-chip{border:none;background:#fff;border-radius:8px;padding:5px 10px;cursor:pointer;display:inline-flex;flex-direction:column;align-items:flex-start;gap:1px;transition:all .12s;border:1px solid #E2E8F0;font-family:inherit}
.sdt-var-chip:hover{border-color:#6366F1;background:#EEF2FF;transform:translateY(-1px);box-shadow:0 2px 8px rgba(99,102,241,.15)}
.sdt-var-name{font-size:10px;font-weight:800;color:#4F46E5;direction:rtl}
.sdt-var-val{font-size:9px;color:#94A3B8;direction:rtl;max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}

.sdt-drafts-list{display:flex;flex-direction:column;gap:4px;max-height:200px;overflow-y:auto}
.sdt-draft-item{display:flex;align-items:center;background:#fff;border:1px solid #E2E8F0;border-radius:8px;overflow:hidden;transition:all .12s}
.sdt-draft-item:hover{border-color:#F59E0B;box-shadow:0 2px 8px rgba(245,158,11,.12)}
.sdt-draft-load{flex:1;padding:7px 10px;cursor:pointer;min-width:0}
.sdt-draft-name{font-size:11px;font-weight:700;color:#1E293B;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sdt-draft-preview{font-size:9px;color:#94A3B8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;direction:rtl;margin-top:1px}
.sdt-draft-del{border:none;background:#FEF2F2;color:#EF4444;padding:8px 10px;cursor:pointer;font-size:13px;transition:all .12s;flex-shrink:0}
.sdt-draft-del:hover{background:#EF4444;color:#fff}
.sdt-empty{text-align:center;padding:16px;color:#94A3B8;font-size:12px;font-weight:600}
.sdt-empty i{font-size:20px;display:block;margin-bottom:6px}
@keyframes sdt-fadeIn{from{opacity:0;transform:translateY(-3px)}to{opacity:1;transform:translateY(0)}}
</style>

<!-- ═══ نافذة إرسال رسالة SMS ═══ -->
<style>
.ssms-modal .modal-content{border:none;border-radius:16px;overflow:hidden;box-shadow:0 25px 60px rgba(0,0,0,.25)}
.ssms-modal .modal-dialog{max-width:520px}
.ssms-hdr{background:linear-gradient(135deg,#0F766E 0%,#0D9488 50%,#14B8A6 100%);padding:16px 24px;position:relative;overflow:hidden}
.ssms-hdr::before{content:'';position:absolute;top:-50%;right:-15%;width:160px;height:160px;background:radial-gradient(circle,rgba(255,255,255,.08) 0%,transparent 70%);border-radius:50%}
.ssms-hdr-top{display:flex;align-items:center;justify-content:space-between;position:relative;z-index:1}
.ssms-hdr-title{display:flex;align-items:center;gap:12px}
.ssms-hdr-title .ssms-hdr-icon{width:38px;height:38px;background:rgba(255,255,255,.18);backdrop-filter:blur(8px);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;color:#fff}
.ssms-hdr-title h4{margin:0;font-size:17px;font-weight:800;color:#fff}
.ssms-hdr-title h4 small{display:block;font-size:11px;font-weight:500;color:rgba(255,255,255,.7);margin-top:1px}
.ssms-hdr .ssms-close{background:rgba(255,255,255,.15);border:none;color:#fff;width:30px;height:30px;border-radius:8px;font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .15s;position:relative;z-index:1}
.ssms-hdr .ssms-close:hover{background:rgba(255,255,255,.3)}
.ssms-phone-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.15);backdrop-filter:blur(6px);padding:5px 14px;border-radius:20px;margin-top:10px;position:relative;z-index:1}
.ssms-phone-badge i{color:rgba(255,255,255,.7);font-size:12px}
.ssms-phone-badge span{color:#fff;font-weight:700;font-size:14px;direction:ltr;font-family:'Courier New',monospace;letter-spacing:.5px}
.ssms-body{padding:16px 20px;background:#F8FAFC}
.ssms-textarea-wrap{position:relative;margin-bottom:10px}
.ssms-textarea-wrap textarea{width:100%;border:1.5px solid #E2E8F0;border-radius:10px;padding:14px 16px 36px;font-size:14px;line-height:1.6;resize:vertical;min-height:110px;outline:none;transition:all .2s;background:#fff;color:#1E293B}
.ssms-textarea-wrap textarea:focus{border-color:#0D9488;box-shadow:0 0 0 3px rgba(13,148,136,.1)}
.ssms-textarea-wrap textarea::placeholder{color:#94A3B8;font-size:12px}
.ssms-emoji-btn{position:absolute;bottom:8px;left:10px;background:none;border:none;font-size:20px;cursor:pointer;opacity:.45;transition:all .2s;padding:2px;line-height:1;border-radius:6px}
.ssms-emoji-btn:hover{opacity:1;background:rgba(13,148,136,.06)}
.ssms-emoji-panel{display:none;background:#fff;border:1.5px solid #E2E8F0;border-radius:10px;padding:10px;margin-bottom:10px;box-shadow:0 6px 20px rgba(0,0,0,.1);max-height:140px;overflow-y:auto}
.ssms-emoji-panel.open{display:block;animation:ssms-fadeIn .15s ease}
.ssms-emoji-panel .ssms-emoji-cat{font-size:8px;font-weight:800;color:#9CA3AF;margin:6px 0 3px;text-transform:uppercase;letter-spacing:.4px}
.ssms-emoji-panel .ssms-emoji-cat:first-child{margin-top:0}
.ssms-emoji-grid{display:flex;flex-wrap:wrap;gap:1px}
.ssms-emoji-grid span{font-size:19px;cursor:pointer;padding:3px 4px;border-radius:6px;transition:all .1s;line-height:1.2}
.ssms-emoji-grid span:hover{background:#F0FDFA;transform:scale(1.15)}
.ssms-clear-btn{background:linear-gradient(135deg,#EF4444,#DC2626);color:#fff;border:none;border-radius:8px;padding:8px;font-size:12px;font-weight:700;cursor:pointer;width:100%;display:flex;align-items:center;justify-content:center;gap:6px;transition:all .15s;margin-bottom:6px}
.ssms-clear-btn:hover{filter:brightness(.92)}
.ssms-stats{display:flex;flex-direction:column;gap:4px}
.ssms-stat-bar{display:flex;align-items:center;border-radius:8px;overflow:hidden;font-size:11px;font-weight:700;height:30px;transition:transform .1s}
.ssms-stat-bar:hover{transform:translateX(-2px)}
.ssms-stat-bar .ssms-sb-label{flex:1;text-align:center;padding:0 10px;white-space:nowrap;letter-spacing:.2px}
.ssms-stat-bar .ssms-sb-value{padding:0 12px;font-size:13px;font-weight:800;min-width:48px;text-align:center;background:rgba(0,0,0,.12);height:100%;display:flex;align-items:center;justify-content:center}
.ssms-stat-bar.sbar-parts{background:linear-gradient(90deg,#14B8A6,#0D9488);color:#fff}
.ssms-stat-bar.sbar-used{background:linear-gradient(90deg,#14B8A6,#0D9488);color:#fff}
.ssms-stat-bar.sbar-remain{background:linear-gradient(90deg,#14B8A6,#0D9488);color:#fff}
.ssms-stat-bar.sbar-encoding{background:linear-gradient(90deg,#6366F1,#4F46E5);color:#fff}
.ssms-footer{background:#fff;border-top:1px solid #E2E8F0;padding:12px 20px;display:flex;gap:8px;flex-direction:row-reverse}
.ssms-footer .ssms-btn-send{background:linear-gradient(135deg,#0D9488,#0F766E);color:#fff;border:none;border-radius:10px;padding:10px 28px;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:8px;transition:all .2s;box-shadow:0 2px 12px rgba(13,148,136,.3)}
.ssms-footer .ssms-btn-send:hover{box-shadow:0 4px 20px rgba(13,148,136,.4);transform:translateY(-1px)}
.ssms-footer .ssms-btn-close{background:#F1F5F9;color:#475569;border:1.5px solid #E2E8F0;border-radius:10px;padding:10px 20px;font-size:13px;font-weight:600;cursor:pointer;transition:all .15s}
.ssms-footer .ssms-btn-close:hover{background:#E2E8F0}
@keyframes ssms-fadeIn{from{opacity:0;transform:translateY(-4px)}to{opacity:1;transform:translateY(0)}}
</style>
<div class="modal fade ssms-modal" id="smsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="ssms-hdr">
                <div class="ssms-hdr-top">
                    <div class="ssms-hdr-title">
                        <div class="ssms-hdr-icon"><i class="fa fa-comment"></i></div>
                        <h4>إرسال رسالة نصية<small>SMS لرقم واحد</small></h4>
                    </div>
                    <button type="button" class="ssms-close" data-bs-dismiss="modal">&times;</button>
                </div>
                <div class="ssms-phone-badge">
                    <i class="fa fa-phone"></i>
                    <span id="ssms-phone-display">—</span>
                </div>
                <input type="hidden" id="phone_number" value="0">
            </div>
            <div class="ssms-body">
                <div class="ssms-textarea-wrap">
                    <textarea id="sms_text" placeholder="اكتب نص الرسالة هنا..." rows="4"></textarea>
                    <button type="button" class="ssms-emoji-btn" onclick="SingleSms.toggleEmoji()" title="إضافة رمز تعبيري">😊</button>
                </div>
                <div class="ssms-emoji-panel" id="ssms-emoji-panel">
                    <div class="ssms-emoji-cat">وجوه</div>
                    <div class="ssms-emoji-grid">
                        <span onclick="SingleSms.insertEmoji('😊')">😊</span><span onclick="SingleSms.insertEmoji('😂')">😂</span><span onclick="SingleSms.insertEmoji('❤️')">❤️</span><span onclick="SingleSms.insertEmoji('😍')">😍</span><span onclick="SingleSms.insertEmoji('🥰')">🥰</span><span onclick="SingleSms.insertEmoji('😘')">😘</span><span onclick="SingleSms.insertEmoji('😁')">😁</span><span onclick="SingleSms.insertEmoji('😎')">😎</span><span onclick="SingleSms.insertEmoji('🤗')">🤗</span><span onclick="SingleSms.insertEmoji('😢')">😢</span><span onclick="SingleSms.insertEmoji('😭')">😭</span><span onclick="SingleSms.insertEmoji('😡')">😡</span><span onclick="SingleSms.insertEmoji('🤔')">🤔</span><span onclick="SingleSms.insertEmoji('😅')">😅</span><span onclick="SingleSms.insertEmoji('🙏')">🙏</span><span onclick="SingleSms.insertEmoji('🤝')">🤝</span>
                    </div>
                    <div class="ssms-emoji-cat">إشارات</div>
                    <div class="ssms-emoji-grid">
                        <span onclick="SingleSms.insertEmoji('👍')">👍</span><span onclick="SingleSms.insertEmoji('👋')">👋</span><span onclick="SingleSms.insertEmoji('✅')">✅</span><span onclick="SingleSms.insertEmoji('❌')">❌</span><span onclick="SingleSms.insertEmoji('⚠️')">⚠️</span><span onclick="SingleSms.insertEmoji('📞')">📞</span><span onclick="SingleSms.insertEmoji('💰')">💰</span><span onclick="SingleSms.insertEmoji('📋')">📋</span><span onclick="SingleSms.insertEmoji('🔔')">🔔</span><span onclick="SingleSms.insertEmoji('⏰')">⏰</span><span onclick="SingleSms.insertEmoji('📅')">📅</span><span onclick="SingleSms.insertEmoji('💳')">💳</span><span onclick="SingleSms.insertEmoji('🏦')">🏦</span><span onclick="SingleSms.insertEmoji('📱')">📱</span><span onclick="SingleSms.insertEmoji('🎉')">🎉</span><span onclick="SingleSms.insertEmoji('⭐')">⭐</span>
                    </div>
                </div>
                <!-- شريط المسودات والمتغيرات -->
                <div class="sdt-toolbar">
                    <button type="button" class="sdt-btn sdt-btn-vars" onclick="SmsDrafts.togglePanel('ssms-vars-panel')"><i class="fa fa-code"></i> متغيرات</button>
                    <button type="button" class="sdt-btn sdt-btn-drafts" onclick="SmsDrafts.togglePanel('ssms-drafts-panel')"><i class="fa fa-bookmark"></i> مسودات</button>
                    <button type="button" class="sdt-btn sdt-btn-save" onclick="SmsDrafts.promptSave('sms_text')"><i class="fa fa-floppy-o"></i> حفظ كمسودة</button>
                    <button type="button" class="ssms-clear-btn" style="width:auto;flex:none;margin:0;padding:6px 12px;font-size:11px" onclick="SingleSms.clearText()"><i class="fa fa-trash"></i> مسح</button>
                </div>
                <div class="sdt-panel" id="ssms-vars-panel">
                    <div class="sdt-panel-title"><i class="fa fa-code"></i> إدراج متغير — يتم تعبئته تلقائياً حسب العقد الحالي</div>
                    <div class="sdt-vars-grid" id="ssms-vars-list"></div>
                </div>
                <div class="sdt-panel" id="ssms-drafts-panel">
                    <div class="sdt-panel-title"><i class="fa fa-bookmark"></i> المسودات المحفوظة (حتى 10)</div>
                    <div class="sdt-drafts-list" id="ssms-drafts-list"></div>
                </div>
                <div class="ssms-stats">
                    <div class="ssms-stat-bar sbar-parts"><span class="ssms-sb-label">عدد الرسائل</span><span class="ssms-sb-value" id="ssms-s-parts">1</span></div>
                    <div class="ssms-stat-bar sbar-used"><span class="ssms-sb-label">الأحرف المستهلكة</span><span class="ssms-sb-value" id="ssms-s-used">0</span></div>
                    <div class="ssms-stat-bar sbar-remain"><span class="ssms-sb-label">الأحرف المتبقية</span><span class="ssms-sb-value" id="ssms-s-remain">70</span></div>
                    <div class="ssms-stat-bar sbar-encoding"><span class="ssms-sb-label">نوع الترميز</span><span class="ssms-sb-value" id="ssms-s-encoding">عربي</span></div>
                </div>
            </div>
            <div class="ssms-footer">
                <button type="button" class="ssms-btn-send" id="send_sms"><i class="fa fa-paper-plane"></i> إرسال</button>
                <button type="button" class="ssms-btn-close" data-bs-dismiss="modal"><i class="fa fa-times"></i> إغلاق</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══ نافذة إرسال رسالة SMS جماعية ═══ -->
<style>
.bsms-modal .modal-content{border:none;border-radius:16px;overflow:hidden;box-shadow:0 25px 60px rgba(0,0,0,.25)}
.bsms-modal .modal-dialog{max-width:920px;margin:30px auto}

.bsms-hdr{background:linear-gradient(135deg,#1E1B4B 0%,#312E81 40%,#4C1D95 100%);padding:16px 24px;position:relative;overflow:hidden}
.bsms-hdr::before{content:'';position:absolute;top:-60%;right:-15%;width:180px;height:180px;background:radial-gradient(circle,rgba(255,255,255,.06) 0%,transparent 70%);border-radius:50%}
.bsms-hdr-top{display:flex;align-items:center;justify-content:space-between;position:relative;z-index:1}
.bsms-hdr-title{display:flex;align-items:center;gap:12px}
.bsms-hdr-title .bsms-hdr-icon{width:38px;height:38px;background:rgba(255,255,255,.15);backdrop-filter:blur(8px);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;color:#E0E7FF}
.bsms-hdr-title h4{margin:0;font-size:17px;font-weight:800;color:#fff;letter-spacing:-.2px}
.bsms-hdr-title h4 small{display:block;font-size:11px;font-weight:500;color:#C4B5FD;margin-top:1px}
.bsms-hdr .bsms-close{background:rgba(255,255,255,.12);border:none;color:#E0E7FF;width:30px;height:30px;border-radius:8px;font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .15s;position:relative;z-index:1}
.bsms-hdr .bsms-close:hover{background:rgba(255,255,255,.25);color:#fff}

.bsms-body{padding:0;background:#F8FAFC}

.bsms-cols{display:flex;min-height:0}
.bsms-col-right{flex:0 0 380px;border-left:1px solid #E2E8F0;display:flex;flex-direction:column;background:#fff}
.bsms-col-left{flex:1;display:flex;flex-direction:column;min-width:0}

.bsms-sec{padding:12px 16px}
.bsms-sec-title{font-size:10px;font-weight:800;color:#6B7280;text-transform:uppercase;letter-spacing:.6px;margin-bottom:8px;display:flex;align-items:center;gap:6px}
.bsms-sec-title i{color:#7C3AED;font-size:12px}

.bsms-toolbar{display:flex;align-items:center;gap:6px;margin-bottom:8px;flex-wrap:wrap}
.bsms-toolbar .bsms-toggle-all{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:6px;border:1.5px solid #E2E8F0;background:#fff;cursor:pointer;font-size:10px;font-weight:700;color:#475569;transition:all .2s}
.bsms-toolbar .bsms-toggle-all:hover{border-color:#7C3AED;color:#7C3AED}
.bsms-toolbar .bsms-selected-count{font-size:11px;color:#7C3AED;font-weight:700;background:#F5F3FF;padding:3px 10px;border-radius:20px;margin-right:auto}

.bsms-list{flex:1;overflow-y:auto;display:flex;flex-direction:column;gap:2px;padding:0 16px 12px;max-height:380px;scrollbar-width:thin;scrollbar-color:#CBD5E1 transparent}
.bsms-list::-webkit-scrollbar{width:3px}
.bsms-list::-webkit-scrollbar-thumb{background:#CBD5E1;border-radius:3px}

.bsms-item{display:flex;align-items:center;gap:8px;padding:7px 10px;background:#FAFBFC;border:1px solid #F1F5F9;border-radius:8px;cursor:pointer;transition:all .15s}
.bsms-item:hover{border-color:#DDD6FE;background:#FAFAFE;box-shadow:0 1px 4px rgba(124,58,237,.06)}
.bsms-item.excluded{opacity:.3;background:#F9FAFB}
.bsms-item.excluded .bsms-num,.bsms-item.excluded .bsms-name{text-decoration:line-through}
.bsms-item input[type=checkbox]{width:15px;height:15px;accent-color:#7C3AED;cursor:pointer;flex-shrink:0}
.bsms-item .bsms-num{font-size:12px;font-weight:700;color:#1E293B;direction:ltr;font-family:'Courier New',monospace;min-width:95px;letter-spacing:.3px}
.bsms-item .bsms-name{font-size:11px;color:#64748B;flex:1;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.bsms-item .bsms-tag{font-size:8px;padding:2px 7px;border-radius:20px;font-weight:700;white-space:nowrap;letter-spacing:.2px}
.bsms-item .bsms-tag.primary{background:#ECFDF5;color:#059669;border:1px solid #A7F3D0}
.bsms-item .bsms-tag.extra{background:#F5F3FF;color:#7C3AED;border:1px solid #DDD6FE}
.bsms-item .bsms-wa-btn{width:24px;height:24px;border:none;border-radius:6px;background:#F0FDF4;color:#16A34A;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;font-size:12px;transition:all .15s;flex-shrink:0;padding:0}
.bsms-item .bsms-wa-btn:hover{background:#DCFCE7;color:#15803D;transform:scale(1.1)}
.bsms-item.excluded .bsms-wa-btn{opacity:.3;pointer-events:none}

.bsms-textarea-wrap{position:relative}
.bsms-textarea-wrap textarea{width:100%;border:1.5px solid #E2E8F0;border-radius:10px;padding:12px 14px 32px;font-size:13px;line-height:1.6;resize:vertical;min-height:120px;outline:none;transition:all .2s;background:#FAFBFC;color:#1E293B}
.bsms-textarea-wrap textarea:focus{border-color:#7C3AED;box-shadow:0 0 0 3px rgba(124,58,237,.08);background:#fff}
.bsms-textarea-wrap textarea::placeholder{color:#94A3B8;font-size:12px}
.bsms-emoji-btn{position:absolute;bottom:8px;left:10px;background:none;border:none;font-size:20px;cursor:pointer;opacity:.45;transition:all .2s;padding:2px;line-height:1;border-radius:6px}
.bsms-emoji-btn:hover{opacity:1;background:rgba(124,58,237,.06)}

.bsms-emoji-panel{display:none;background:#fff;border:1.5px solid #E2E8F0;border-radius:10px;padding:10px;margin-top:6px;box-shadow:0 6px 20px rgba(0,0,0,.1);max-height:130px;overflow-y:auto}
.bsms-emoji-panel.open{display:block;animation:bsms-fadeIn .15s ease}
.bsms-emoji-panel .bsms-emoji-cat{font-size:8px;font-weight:800;color:#9CA3AF;margin:6px 0 3px;text-transform:uppercase;letter-spacing:.4px}
.bsms-emoji-panel .bsms-emoji-cat:first-child{margin-top:0}
.bsms-emoji-grid{display:flex;flex-wrap:wrap;gap:1px}
.bsms-emoji-grid span{font-size:19px;cursor:pointer;padding:3px 4px;border-radius:6px;transition:all .1s;line-height:1.2}
.bsms-emoji-grid span:hover{background:#F5F3FF;transform:scale(1.15)}

.bsms-clear-btn{background:linear-gradient(135deg,#EF4444,#DC2626);color:#fff;border:none;border-radius:8px;padding:8px;font-size:12px;font-weight:700;cursor:pointer;width:100%;display:flex;align-items:center;justify-content:center;gap:6px;transition:all .15s;margin-bottom:6px}
.bsms-clear-btn:hover{filter:brightness(.92)}

.bsms-stats{display:flex;flex-direction:column;gap:4px}
.bsms-stat-bar{display:flex;align-items:center;border-radius:8px;overflow:hidden;font-size:11px;font-weight:700;height:30px;transition:transform .1s}
.bsms-stat-bar:hover{transform:translateX(-2px)}
.bsms-stat-bar .bsms-sb-label{flex:1;text-align:center;padding:0 10px;white-space:nowrap;letter-spacing:.2px}
.bsms-stat-bar .bsms-sb-value{padding:0 12px;font-size:13px;font-weight:800;min-width:48px;text-align:center;background:rgba(0,0,0,.12);height:100%;display:flex;align-items:center;justify-content:center}
.bsms-stat-bar.bar-parts{background:linear-gradient(90deg,#14B8A6,#0D9488);color:#fff}
.bsms-stat-bar.bar-used{background:linear-gradient(90deg,#14B8A6,#0D9488);color:#fff}
.bsms-stat-bar.bar-remain{background:linear-gradient(90deg,#14B8A6,#0D9488);color:#fff}
.bsms-stat-bar.bar-encoding{background:linear-gradient(90deg,#6366F1,#4F46E5);color:#fff}
.bsms-stat-bar.bar-total{background:linear-gradient(90deg,#F43F5E,#E11D48);color:#fff;box-shadow:0 2px 6px rgba(244,63,94,.2)}

.bsms-progress{padding:12px 16px;display:none}
.bsms-progress-bar-wrap{background:#E2E8F0;border-radius:10px;height:5px;overflow:hidden;margin-bottom:8px}
.bsms-progress-bar-fill{height:100%;background:linear-gradient(90deg,#7C3AED,#A78BFA);border-radius:10px;transition:width .3s ease;width:0}
.bsms-progress-text{font-size:11px;color:#475569;text-align:center;font-weight:600}

.bsms-results{padding:6px 16px 12px;display:none;max-height:150px;overflow-y:auto}
.bsms-result-item{display:flex;align-items:center;gap:6px;padding:4px 10px;font-size:11px;background:#fff;border-radius:6px;margin-bottom:2px;border:1px solid #F1F5F9}
.bsms-result-item .fa-check-circle{color:#16A34A;font-size:13px}
.bsms-result-item .fa-times-circle{color:#EF4444;font-size:13px}
.bsms-result-item .bsms-r-num{direction:ltr;font-family:'Courier New',monospace;font-weight:700;min-width:95px;color:#1E293B;font-size:11px}

.bsms-footer{background:#fff;border-top:1px solid #E2E8F0;padding:12px 20px;display:flex;gap:8px;flex-direction:row-reverse}
.bsms-footer .bsms-btn-send{background:linear-gradient(135deg,#7C3AED,#6D28D9);color:#fff;border:none;border-radius:10px;padding:10px 28px;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:8px;transition:all .2s;box-shadow:0 2px 12px rgba(124,58,237,.3)}
.bsms-footer .bsms-btn-send:hover:not(:disabled){box-shadow:0 4px 20px rgba(124,58,237,.4);transform:translateY(-1px)}
.bsms-footer .bsms-btn-send:disabled{opacity:.5;cursor:not-allowed}
.bsms-footer .bsms-btn-wa{background:linear-gradient(135deg,#25D366,#128C7E);color:#fff;border:none;border-radius:10px;padding:10px 24px;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:8px;transition:all .2s;box-shadow:0 2px 12px rgba(37,211,102,.3)}
.bsms-footer .bsms-btn-wa:hover:not(:disabled){box-shadow:0 4px 20px rgba(37,211,102,.4);transform:translateY(-1px)}
.bsms-footer .bsms-btn-wa:disabled{opacity:.5;cursor:not-allowed}
.bsms-footer .bsms-btn-close{background:#F1F5F9;color:#475569;border:1.5px solid #E2E8F0;border-radius:10px;padding:10px 20px;font-size:13px;font-weight:600;cursor:pointer;transition:all .15s}
.bsms-footer .bsms-btn-close:hover{background:#E2E8F0;border-color:#CBD5E1}

@keyframes bsms-fadeIn{from{opacity:0;transform:translateY(-4px)}to{opacity:1;transform:translateY(0)}}
@media(max-width:768px){
.bsms-modal .modal-dialog{max-width:95%;margin:15px auto}
.bsms-cols{flex-direction:column}
.bsms-col-right{flex:none;border-left:none;border-bottom:1px solid #E2E8F0}
.bsms-list{max-height:180px}
}
</style>
<div class="modal fade bsms-modal" id="bulkSmsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <!-- Header -->
            <div class="bsms-hdr">
                <div class="bsms-hdr-top">
                    <div class="bsms-hdr-title">
                        <div class="bsms-hdr-icon"><i class="fa fa-paper-plane"></i></div>
                        <h4>رسالة SMS جماعية<small>إرسال رسالة واحدة لجميع الأرقام المحددة</small></h4>
                    </div>
                    <button type="button" class="bsms-close" data-bs-dismiss="modal">&times;</button>
                </div>
            </div>

            <!-- Body — two columns -->
            <div class="bsms-body">
                <div class="bsms-cols">
                    <!-- العمود الأيمن: الأرقام -->
                    <div class="bsms-col-right">
                        <div class="bsms-sec" style="padding-bottom:0">
                            <div class="bsms-sec-title"><i class="fa fa-phone"></i> المستلمون</div>
                            <div class="bsms-toolbar">
                                <button type="button" class="bsms-toggle-all" onclick="BulkSms.toggleAll()">
                                    <i class="fa fa-check-square-o" id="bsms-toggle-icon"></i>
                                    <span id="bsms-toggle-text">إلغاء تحديد الكل</span>
                                </button>
                                <span class="bsms-selected-count"><i class="fa fa-users"></i> <span id="bsms-sel-count">0</span> / <span id="bsms-total-count">0</span></span>
                            </div>
                        </div>
                        <div class="bsms-list" id="bsms-list"></div>
                    </div>

                    <!-- العمود الأيسر: الرسالة + الإحصائيات -->
                    <div class="bsms-col-left">
                        <!-- كتابة الرسالة -->
                        <div class="bsms-sec" style="flex:1">
                            <div class="bsms-sec-title"><i class="fa fa-pencil-square-o"></i> نص الرسالة</div>
                            <div class="bsms-textarea-wrap">
                                <textarea id="bsms-text" placeholder="اكتب نص الرسالة هنا..." rows="4"></textarea>
                                <button type="button" class="bsms-emoji-btn" onclick="BulkSms.toggleEmoji()" title="إضافة رمز تعبيري">😊</button>
                            </div>
                            <div class="bsms-emoji-panel" id="bsms-emoji-panel">
                                <div class="bsms-emoji-cat">وجوه</div>
                                <div class="bsms-emoji-grid">
                                    <span onclick="BulkSms.insertEmoji('😊')">😊</span><span onclick="BulkSms.insertEmoji('😂')">😂</span><span onclick="BulkSms.insertEmoji('❤️')">❤️</span><span onclick="BulkSms.insertEmoji('😍')">😍</span><span onclick="BulkSms.insertEmoji('🥰')">🥰</span><span onclick="BulkSms.insertEmoji('😘')">😘</span><span onclick="BulkSms.insertEmoji('😁')">😁</span><span onclick="BulkSms.insertEmoji('😎')">😎</span><span onclick="BulkSms.insertEmoji('🤗')">🤗</span><span onclick="BulkSms.insertEmoji('😢')">😢</span><span onclick="BulkSms.insertEmoji('😭')">😭</span><span onclick="BulkSms.insertEmoji('😡')">😡</span><span onclick="BulkSms.insertEmoji('🤔')">🤔</span><span onclick="BulkSms.insertEmoji('😅')">😅</span><span onclick="BulkSms.insertEmoji('🙏')">🙏</span><span onclick="BulkSms.insertEmoji('🤝')">🤝</span>
                                </div>
                                <div class="bsms-emoji-cat">إشارات</div>
                                <div class="bsms-emoji-grid">
                                    <span onclick="BulkSms.insertEmoji('👍')">👍</span><span onclick="BulkSms.insertEmoji('👋')">👋</span><span onclick="BulkSms.insertEmoji('✅')">✅</span><span onclick="BulkSms.insertEmoji('❌')">❌</span><span onclick="BulkSms.insertEmoji('⚠️')">⚠️</span><span onclick="BulkSms.insertEmoji('📞')">📞</span><span onclick="BulkSms.insertEmoji('💰')">💰</span><span onclick="BulkSms.insertEmoji('📋')">📋</span><span onclick="BulkSms.insertEmoji('🔔')">🔔</span><span onclick="BulkSms.insertEmoji('⏰')">⏰</span><span onclick="BulkSms.insertEmoji('📅')">📅</span><span onclick="BulkSms.insertEmoji('💳')">💳</span><span onclick="BulkSms.insertEmoji('🏦')">🏦</span><span onclick="BulkSms.insertEmoji('📱')">📱</span><span onclick="BulkSms.insertEmoji('🎉')">🎉</span><span onclick="BulkSms.insertEmoji('⭐')">⭐</span>
                                </div>
                                <div class="bsms-emoji-cat">أرقام</div>
                                <div class="bsms-emoji-grid">
                                    <span onclick="BulkSms.insertEmoji('0️⃣')">0️⃣</span><span onclick="BulkSms.insertEmoji('1️⃣')">1️⃣</span><span onclick="BulkSms.insertEmoji('2️⃣')">2️⃣</span><span onclick="BulkSms.insertEmoji('3️⃣')">3️⃣</span><span onclick="BulkSms.insertEmoji('4️⃣')">4️⃣</span><span onclick="BulkSms.insertEmoji('5️⃣')">5️⃣</span><span onclick="BulkSms.insertEmoji('6️⃣')">6️⃣</span><span onclick="BulkSms.insertEmoji('7️⃣')">7️⃣</span><span onclick="BulkSms.insertEmoji('8️⃣')">8️⃣</span><span onclick="BulkSms.insertEmoji('9️⃣')">9️⃣</span>
                                </div>
                            </div>
                        </div>

                        <!-- شريط المسودات والمتغيرات -->
                        <div class="bsms-sec" style="padding-top:4px;padding-bottom:0">
                            <div class="sdt-toolbar">
                                <button type="button" class="sdt-btn sdt-btn-vars" onclick="SmsDrafts.togglePanel('bsms-vars-panel')"><i class="fa fa-code"></i> متغيرات</button>
                                <button type="button" class="sdt-btn sdt-btn-drafts" onclick="SmsDrafts.togglePanel('bsms-drafts-panel')"><i class="fa fa-bookmark"></i> مسودات</button>
                                <button type="button" class="sdt-btn sdt-btn-save" onclick="SmsDrafts.promptSave('bsms-text')"><i class="fa fa-floppy-o"></i> حفظ كمسودة</button>
                                <button type="button" class="bsms-clear-btn" style="width:auto;flex:none;margin:0;padding:6px 12px;font-size:11px" onclick="BulkSms.clearText()"><i class="fa fa-trash"></i> مسح</button>
                            </div>
                            <div class="sdt-panel" id="bsms-vars-panel">
                                <div class="sdt-panel-title"><i class="fa fa-code"></i> إدراج متغير — يتم تعبئته تلقائياً حسب العقد الحالي</div>
                                <div class="sdt-vars-grid" id="bsms-vars-list"></div>
                            </div>
                            <div class="sdt-panel" id="bsms-drafts-panel">
                                <div class="sdt-panel-title"><i class="fa fa-bookmark"></i> المسودات المحفوظة (حتى 10)</div>
                                <div class="sdt-drafts-list" id="bsms-drafts-list"></div>
                            </div>
                        </div>

                        <!-- الإحصائيات -->
                        <div class="bsms-sec" style="padding-top:6px;background:#F8FAFC;border-top:1px solid #E2E8F0">
                            <div class="bsms-stats" id="bsms-stats">
                                <div class="bsms-stat-bar bar-parts"><span class="bsms-sb-label">عدد الرسائل</span><span class="bsms-sb-value" id="bsms-s-parts">1</span></div>
                                <div class="bsms-stat-bar bar-used"><span class="bsms-sb-label">الأحرف المستهلكة</span><span class="bsms-sb-value" id="bsms-s-used">0</span></div>
                                <div class="bsms-stat-bar bar-remain"><span class="bsms-sb-label">الأحرف المتبقية</span><span class="bsms-sb-value" id="bsms-s-remain">70</span></div>
                                <div class="bsms-stat-bar bar-encoding"><span class="bsms-sb-label">نوع الترميز</span><span class="bsms-sb-value" id="bsms-s-encoding">عربي</span></div>
                                <div class="bsms-stat-bar bar-total"><span class="bsms-sb-label">إجمالي الرسائل (الكل)</span><span class="bsms-sb-value" id="bsms-s-total">0</span></div>
                            </div>
                        </div>

                        <!-- تقدم الإرسال -->
                        <div class="bsms-progress" id="bsms-progress">
                            <div class="bsms-progress-bar-wrap"><div class="bsms-progress-bar-fill" id="bsms-progress-fill"></div></div>
                            <div class="bsms-progress-text" id="bsms-progress-text">جاري الإرسال...</div>
                        </div>
                        <div class="bsms-results" id="bsms-results"></div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="bsms-footer">
                <button type="button" class="bsms-btn-send" id="bsms-send-btn" onclick="BulkSms.send()">
                    <i class="fa fa-paper-plane"></i> إرسال SMS للمحددين
                </button>
                <button type="button" class="bsms-btn-wa" id="bsms-wa-send-btn" onclick="BulkSms.sendWhatsApp()">
                    <i class="fa fa-whatsapp"></i> إرسال واتساب للمحددين
                </button>
                <button type="button" class="bsms-btn-close" data-bs-dismiss="modal"><i class="fa fa-times"></i> إغلاق</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══ نافذة تغيير حالة العقد ═══ -->
<div class="modal fade" id="changeStatusModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-bs-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-exchange"></i> تغيير حالة العقد</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>الحالة الجديدة</label>
                    <select class="form-control status-content">
                        <option value="canceled">إلغاء العقد</option>
                        <option value="legal_department_toggle">تحويل / إلغاء تحويل للدائرة القانونية</option>
                    </select>
                    <p class="help-block" style="margin-top:8px;color:#888">
                        <i class="fa fa-info-circle"></i>
                        الحالات الأخرى (قضائي، منتهي، تسوية) تُحسب تلقائياً بناءً على الإجراءات الفعلية
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa fa-times"></i> إلغاء</button>
                <button type="button" class="btn btn-primary statse-change" contract-id="<?= $contractModel->id ?>">
                    <i class="fa fa-save"></i> حفظ التغيير
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ═══ نافذة بيانات العميل (تعديل مباشر) ═══ -->
<style>
.ci-modal .modal-header{background:linear-gradient(135deg,var(--ocp-primary,#6B1D3D),#9B2C5A);color:#fff;border-radius:4px 4px 0 0;padding:14px 20px}
.ci-modal .modal-header .close{color:#fff;opacity:.7;text-shadow:none}
.ci-modal .modal-header .close:hover{opacity:1}
.ci-modal .modal-title{font-size:15px;font-weight:700}
.ci-modal .modal-body{padding:0}
.ci-section{padding:16px 20px;border-bottom:1px solid #F1F5F9}
.ci-section:last-child{border-bottom:none}
.ci-section-title{font-size:11px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:.4px;margin-bottom:10px;display:flex;align-items:center;gap:6px}
.ci-section-title i{color:var(--ocp-primary,#6B1D3D);font-size:13px}
.ci-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px}
.ci-field{background:#FAFBFC;border-radius:8px;padding:8px 12px;border:1px solid #F1F5F9;cursor:pointer;transition:all .2s}
.ci-field:hover{border-color:#CBD5E1;background:#F8FAFC}
.ci-field-label{font-size:10px;font-weight:600;color:#94A3B8;margin-bottom:2px}
.ci-field-value{font-size:13px;font-weight:600;color:#1E293B;min-height:18px}
.ci-field.full{grid-column:1/-1}
.ci-modal .modal-footer{border-top:1px solid #E2E8F0;padding:10px 20px;display:flex;gap:8px;justify-content:flex-end}
.ci-modal .modal-footer .btn{border-radius:8px;font-size:12px;font-weight:600;padding:8px 16px}
.ci-input{width:100%;border:1.5px solid #E2E8F0;border-radius:6px;padding:4px 8px;font-size:13px;font-weight:600;color:#1E293B;background:#fff;outline:none;transition:border-color .2s,box-shadow .2s}
.ci-input:focus{border-color:var(--ocp-primary,#6B1D3D);box-shadow:0 0 0 3px rgba(107,29,61,.1)}
.ci-input:disabled{background:#FAFBFC;border-color:transparent;color:#1E293B;cursor:pointer;-webkit-appearance:none;appearance:none}
.ci-input[disabled]::-webkit-calendar-picker-indicator{display:none}
select.ci-input:disabled{-webkit-appearance:none;-moz-appearance:none;background-image:none}
select.ci-input:not(:disabled){-webkit-appearance:auto;-moz-appearance:auto}
.ci-field .select2-container{width:100%!important}
.ci-field .select2-container--bootstrap4 .select2-selection--single{border:1.5px solid #E2E8F0;border-radius:6px;height:29px;padding:2px 8px;font-size:13px;font-weight:600;color:#1E293B;background:#fff}
.ci-field .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered{color:#1E293B;padding:0;line-height:23px;font-weight:600;font-size:13px}
.ci-field .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow{height:27px}
.ci-field.ci-editing .select2-container--bootstrap4 .select2-selection--single{border-color:var(--ocp-primary,#6B1D3D);box-shadow:0 0 0 3px rgba(107,29,61,.1)}
.ci-field .select2-container--disabled .select2-selection--single{background:#FAFBFC;border-color:transparent;cursor:pointer}
.ci-field .select2-container--disabled .select2-selection--single .select2-selection__arrow{display:none}
textarea.ci-input{resize:vertical;min-height:50px}
textarea.ci-input:disabled{resize:none}
.ci-field.ci-editing{border-color:var(--ocp-primary,#6B1D3D);background:#fff;box-shadow:0 0 0 3px rgba(107,29,61,.08)}
.ci-field .ci-edit-hint{font-size:9px;color:#CBD5E1;margin-top:2px;display:block;transition:opacity .2s}
.ci-field.ci-editing .ci-edit-hint{opacity:0}
.ci-save-bar{background:#F0FDF4;border:1px solid #BBF7D0;border-radius:8px;padding:10px 16px;margin:12px 20px;display:none;align-items:center;gap:10px}
.ci-save-bar.visible{display:flex}
.ci-save-bar .ci-save-text{flex:1;font-size:12px;color:#166534;font-weight:600}
.ci-save-bar .btn-ci-save{background:#16A34A;color:#fff;border:none;border-radius:6px;padding:6px 20px;font-size:12px;font-weight:700;cursor:pointer}
.ci-save-bar .btn-ci-save:hover{background:#15803D}
.ci-save-bar .btn-ci-cancel{background:none;border:1px solid #D1D5DB;border-radius:6px;padding:6px 14px;font-size:12px;color:#6B7280;cursor:pointer}
.ci-save-bar .btn-ci-cancel:hover{background:#F3F4F6}
@keyframes ciShake{0%,100%{transform:translateX(0)}20%,60%{transform:translateX(-4px)}40%,80%{transform:translateX(4px)}}
</style>
<?php
$cities = \yii\helpers\ArrayHelper::map(\backend\modules\city\models\City::find()->orderBy('name')->asArray()->all(), 'id', 'name');
$jobs = \yii\helpers\ArrayHelper::map(\backend\modules\jobs\models\Jobs::find()->orderBy('name')->asArray()->all(), 'id', 'name');
$banks = \yii\helpers\ArrayHelper::map(\backend\modules\bancks\models\Bancks::find()->orderBy('name')->asArray()->all(), 'id', 'name');
$statuses = \yii\helpers\ArrayHelper::map(\backend\modules\status\models\Status::find()->asArray()->all(), 'id', 'name');
$citizens = \yii\helpers\ArrayHelper::map(\backend\modules\citizen\models\Citizen::find()->asArray()->all(), 'id', 'name');
$hearAboutUs = \yii\helpers\ArrayHelper::map(\backend\modules\hearAboutUs\models\HearAboutUs::find()->asArray()->all(), 'id', 'name');

$selectOpts = function($items, $cls, $field) {
    $html = '<select class="ci-input ' . $cls . '" data-field="' . $field . '" disabled><option value="">—</option>';
    foreach ($items as $id => $name) {
        $html .= '<option value="' . Html::encode($id) . '">' . Html::encode($name) . '</option>';
    }
    $html .= '</select>';
    return $html;
};
?>
<div class="modal fade ci-modal" id="customerInfoModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-bs-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title" id="customerInfoTitle"><i class="fa fa-user-circle"></i> بيانات العميل</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="ci-customer-id" value="">

                <div class="ci-save-bar" id="ciSaveBar">
                    <i class="fa fa-info-circle" style="color:#16A34A;font-size:16px"></i>
                    <span class="ci-save-text">تم تعديل بعض الحقول — اضغط "حفظ" لتطبيق التغييرات</span>
                    <button type="button" class="btn-ci-cancel" onclick="CiEdit.cancelAll()"><i class="fa fa-undo"></i> تراجع</button>
                    <button type="button" class="btn-ci-save" onclick="CiEdit.save()"><i class="fa fa-check"></i> حفظ التعديلات</button>
                </div>

                <div class="ci-section">
                    <div class="ci-section-title"><i class="fa fa-id-card"></i> المعلومات الشخصية</div>
                    <div class="ci-grid">
                        <div class="ci-field"><div class="ci-field-label">الاسم الكامل</div><input type="text" class="ci-input cu-name" data-field="name" disabled><span class="ci-edit-hint">انقر مرتين للتعديل</span></div>
                        <div class="ci-field"><div class="ci-field-label">الرقم الوطني</div><input type="text" class="ci-input cu-id-number" data-field="id_number" disabled><span class="ci-edit-hint">انقر مرتين للتعديل</span></div>
                        <div class="ci-field"><div class="ci-field-label">تاريخ الميلاد</div><input type="date" class="ci-input cu-birth-date" data-field="birth_date" disabled><span class="ci-edit-hint">انقر مرتين للتعديل</span></div>
                        <div class="ci-field"><div class="ci-field-label">المدينة</div><?= $selectOpts($cities, 'cu-city', 'city') ?><span class="ci-edit-hint">انقر مرتين للتعديل</span></div>
                        <div class="ci-field"><div class="ci-field-label">الجنس</div><select class="ci-input cu-sex" data-field="sex" disabled><option value="">—</option><option value="0">ذكر</option><option value="1">أنثى</option></select><span class="ci-edit-hint">انقر مرتين للتعديل</span></div>
                    </div>
                </div>
                <div class="ci-section">
                    <div class="ci-section-title"><i class="fa fa-briefcase"></i> معلومات العمل</div>
                    <div class="ci-grid">
                        <div class="ci-field"><div class="ci-field-label">الوظيفة</div><?= $selectOpts($jobs, 'cu-job-title', 'job_title') ?><span class="ci-edit-hint">انقر مرتين للتعديل</span></div>
                        <div class="ci-field"><div class="ci-field-label">الرقم الوظيفي</div><input type="text" class="ci-input cu-job-number" data-field="job_number" disabled><span class="ci-edit-hint">انقر مرتين للتعديل</span></div>
                        <div class="ci-field"><div class="ci-field-label">البريد الإلكتروني</div><input type="email" class="ci-input cu-email" data-field="email" disabled><span class="ci-edit-hint">انقر مرتين للتعديل</span></div>
                    </div>
                </div>
                <div class="ci-section">
                    <div class="ci-section-title"><i class="fa fa-university"></i> المعلومات المالية</div>
                    <div class="ci-grid">
                        <div class="ci-field"><div class="ci-field-label">البنك</div><?= $selectOpts($banks, 'cu-bank-name', 'bank_name') ?><span class="ci-edit-hint">انقر مرتين للتعديل</span></div>
                        <div class="ci-field"><div class="ci-field-label">رقم الحساب</div><input type="text" class="ci-input cu-account-number" data-field="account_number" disabled><span class="ci-edit-hint">انقر مرتين للتعديل</span></div>
                        <div class="ci-field"><div class="ci-field-label">الفرع</div><input type="text" class="ci-input cu-bank-branch" data-field="bank_branch" disabled><span class="ci-edit-hint">انقر مرتين للتعديل</span></div>
                        <div class="ci-field"><div class="ci-field-label">ضمان اجتماعي</div><select class="ci-input cu-is-social-security" data-field="is_social_security" disabled><option value="">—</option><option value="0">لا</option><option value="1">نعم</option></select><span class="ci-edit-hint">انقر مرتين للتعديل</span></div>
                        <div class="ci-field"><div class="ci-field-label">رقم الضمان</div><input type="text" class="ci-input cu-social-security-number" data-field="social_security_number" disabled><span class="ci-edit-hint">انقر مرتين للتعديل</span></div>
                        <div class="ci-field"><div class="ci-field-label">يملك عقارات</div><select class="ci-input cu-do-have-any-property" data-field="do_have_any_property" disabled><option value="">—</option><option value="0">لا</option><option value="1">نعم</option></select><span class="ci-edit-hint">انقر مرتين للتعديل</span></div>
                    </div>
                </div>
                <div class="ci-section">
                    <div class="ci-section-title"><i class="fa fa-sticky-note-o"></i> ملاحظات</div>
                    <div class="ci-field full"><div class="ci-field-label">الملاحظات</div><textarea class="ci-input cu-notes" data-field="notes" rows="2" disabled></textarea><span class="ci-edit-hint">انقر مرتين للتعديل</span></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa fa-times"></i> إغلاق</button>
                <button type="button" class="btn btn-success" id="ciFooterSaveBtn" onclick="CiEdit.save()" style="display:none;border-radius:8px;font-size:12px;font-weight:700;padding:8px 20px"><i class="fa fa-check"></i> حفظ التعديلات</button>
                <a class="btn btn-primary" id="cus-link" style="background:var(--ocp-primary,#6B1D3D);border-color:var(--ocp-primary,#6B1D3D)" target="_blank"><i class="fa fa-external-link"></i> فتح صفحة العميل</a>
            </div>
        </div>
    </div>
</div>
<?php
$this->registerJs(<<<'CISELECT2'
(function(){
    var $modal = $('#customerInfoModal');
    var ciSelects = $modal.find('select.ci-input').filter(function(){
        return this.options.length >= 6;
    });
    ciSelects.each(function(){
        var $sel = $(this);
        $sel.select2({
            theme: 'bootstrap4',
            dir: 'rtl',
            width: '100%',
            allowClear: true,
            placeholder: '—',
            dropdownParent: $modal
        });
        $sel.next('.select2-container').on('dblclick', function(e){
            e.stopPropagation();
            CiEdit.toggleField($sel[0]);
        });
    });
})();
CISELECT2
);
?>

<!-- ═══ نافذة صور العملاء ═══ -->
<div class="modal fade" id="customerImagesModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-bs-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-image"></i> صور ومستندات العملاء</h4>
            </div>
            <div class="modal-body">
                <?php
                /**
                 * === نظام عرض صور العملاء ===
                 *
                 * ثلاثة مصادر للصور في os_ImageManager:
                 * 1. صور قديمة: groupName='coustmers' + contractId = customer_id
                 * 2. صور يتيمة: groupName='coustmers' + contractId عشوائي (يُكتشف عبر selected_image)
                 * 3. صور SmartMedia الجديدة: customer_id مباشرة + groupName رقمي ('0','8','9'...) + contractId=NULL
                 */

                $contractCustomerIds = \backend\modules\customers\models\ContractsCustomers::find()
                    ->select('customer_id')
                    ->where(['contract_id' => $contractModel->id])
                    ->column();

                $hasAnyImages = false;

                /* ══════════════════════════════════════════
                   جلب جميع صور العملاء من ImageManager
                   (المصدر الوحيد الفعلي للصور)
                   ══════════════════════════════════════════ */
                $allImages = [];
                if (!empty($contractCustomerIds)) {

                    // === الاستعلام 1: الصور المربوطة مباشرة (contractId = customer_id, groupName='coustmers') ===
                    try {
                        $directImages = \backend\models\Media::find()
                            ->where(['groupName' => 'coustmers'])
                            ->andWhere(['contractId' => $contractCustomerIds])
                            ->all();
                        foreach ($directImages as $img) {
                            $allImages[$img->id] = $img;
                        }
                    } catch (\Exception $e) {}

                    // === الاستعلام 2: الصور اليتيمة عبر selected_image → contractId ===
                    try {
                        $selectedImageIds = \backend\modules\customers\models\Customers::find()
                            ->select('selected_image')
                            ->where(['id' => $contractCustomerIds])
                            ->andWhere(['not', ['selected_image' => null]])
                            ->andWhere(['!=', 'selected_image', ''])
                            ->andWhere(['!=', 'selected_image', '0'])
                            ->column();

                        if (!empty($selectedImageIds)) {
                            $orphanContractIds = \backend\models\Media::find()
                                ->select('contractId')
                                ->where(['id' => $selectedImageIds])
                                ->andWhere(['groupName' => 'coustmers'])
                                ->column();

                            $customerIdsNormalized = array_map('strval', $contractCustomerIds);
                            $orphanContractIds = array_values(array_filter($orphanContractIds, function ($cid) use ($customerIdsNormalized) {
                                return !in_array((string) $cid, $customerIdsNormalized, true);
                            }));

                            if (!empty($orphanContractIds)) {
                                $orphanImages = \backend\models\Media::find()
                                    ->where(['groupName' => 'coustmers'])
                                    ->andWhere(['contractId' => $orphanContractIds])
                                    ->all();
                                foreach ($orphanImages as $img) {
                                    $allImages[$img->id] = $img;
                                }
                            }
                        }
                    } catch (\Exception $e) {}

                    // === الاستعلام 3: صور SmartMedia المرفوعة بعمود customer_id (بدون contractId) ===
                    try {
                        $smartMediaImages = \backend\models\Media::find()
                            ->where(['customer_id' => $contractCustomerIds])
                            ->andWhere(['not', ['fileHash' => null]])
                            ->andWhere(['!=', 'fileHash', ''])
                            ->all();
                        foreach ($smartMediaImages as $img) {
                            $allImages[$img->id] = $img;
                        }
                    } catch (\Exception $e) {}
                }

                if (!empty($allImages)):
                    $hasAnyImages = true;
                    // ترتيب بالأحدث أولاً
                    krsort($allImages);
                ?>
                    <h5 style="margin-bottom:12px"><i class="fa fa-picture-o"></i> صور العملاء <span class="badge"><?= count($allImages) ?></span></h5>
                    <div class="row">
                        <?php
                            // على نماء نستخدم action تعمل كـ proxy وتجلب الصورة من جادل (نفس النطاق → لا مشاكل referrer/CORS)
                            $isNamaa = stripos((string) Yii::$app->request->hostInfo, 'namaa') !== false;
                        ?>
                        <?php foreach ($allImages as $ei): ?>
                            <?php
                            if (empty($ei->fileHash)) continue;
                            if ($isNamaa) {
                                $path = \yii\helpers\Url::to(['/followUp/follow-up/customer-image', 'id' => $ei->id]);
                            } else {
                                if (!$ei->fileExists()) continue;
                                $path = $ei->getAbsoluteUrl();
                            }
                            ?>
                            <div class="col-md-3 text-center" style="margin-bottom:12px">
                                <a href="<?= Html::encode($path) ?>" target="_blank">
                                    <img src="<?= Html::encode($path) ?>"
                                         style="width:120px;height:120px;object-fit:contain;border-radius:8px;border:1px solid #ddd;padding:4px;cursor:pointer"
                                         alt="صورة عميل"
                                         onerror="this.style.display='none'; this.parentNode.innerHTML='<span style=\'color:#999;font-size:11px\'>صورة غير متوفرة</span>';">
                                </a>
                            </div>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>

                <?php if (!$hasAnyImages): ?>
                    <div class="alert alert-warning" style="text-align:center;border-radius:8px">
                        <i class="fa fa-info-circle"></i> لم يتم العثور على صور لهذا العقد
                    </div>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<!-- ═══ نافذة التدقيق ═══ -->
<div class="modal fade" id="auditModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-bs-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-check-square-o"></i> تدقيق عقد #<?= $contract_id ?></h4>
            </div>
            <div class="modal-body" id="auditDisplay" ondblclick="copyText(this)" style="direction:rtl;text-align:right">
                <?php
                /* معلومات العملاء */
                $contractObj = Contracts::findOne($contract_id);
                if ($contractObj):
                    $allCustomers = $contractObj->customersAndGuarantor ?? [];
                ?>
                    <h4><i class="fa fa-users"></i> معلومات العملاء</h4>
                    <?php foreach ($allCustomers as $cust): ?>
                        <div class="p-3 bg-light rounded small">
                            <strong>العميل:</strong> <?= Html::encode($cust->name) ?><br>
                            <strong>الرقم الوطني:</strong> <?= Html::encode($cust->id_number) ?><br>
                            <?php if (!empty($cust->city)):
                                $cityObj = \backend\modules\city\models\City::findOne($cust->city);
                            ?>
                                <strong>المدينة:</strong> <?= $cityObj ? Html::encode($cityObj->name) : 'لا يوجد' ?><br>
                            <?php endif ?>
                            <?php if (!empty($cust->job_title)):
                                $jobObj = \backend\modules\jobs\models\Jobs::findOne($cust->job_title);
                            ?>
                                <strong>الوظيفة:</strong> <?= $jobObj ? Html::encode($jobObj->name) : 'لا يوجد' ?><br>
                            <?php endif ?>

                            <?php
                            $addrs = \backend\modules\address\models\Address::find()->where(['customers_id' => $cust->id])->all();
                            if (!empty($addrs)): ?>
                                <strong>العناوين:</strong>
                                <ul style="padding-right:20px;margin:5px 0">
                                    <?php foreach ($addrs as $a): ?>
                                        <li><?= ($a->address_type == 1 ? 'عنوان العمل' : 'عنوان السكن') ?>: <?= Html::encode($a->address ?: 'لا يوجد') ?></li>
                                    <?php endforeach ?>
                                </ul>
                            <?php endif ?>
                        </div>
                    <?php endforeach ?>

                    <!-- المعرّفون -->
                    <h4><i class="fa fa-address-book"></i> المعرّفون</h4>
                    <?php foreach ($contractObj->contractsCustomers as $cc): ?>
                        <?php if ($cc->customer && $cc->customer->phoneNumbers): ?>
                            <?php foreach ($cc->customer->phoneNumbers as $pn): ?>
                                <?php $rel = \backend\modules\cousins\models\Cousins::findOne($pn->phone_number_owner); ?>
                                <span class="badge bg-info" style="margin-left:5px">
                                    <?= Html::encode($pn->owner_name) ?> (<?= $rel ? Html::encode($rel->name) : '—' ?>)
                                </span>
                            <?php endforeach ?>
                        <?php endif ?>
                    <?php endforeach ?>

                    <!-- معلومات قضائية -->
                    <?php
                    $judicaries = \backend\modules\judiciary\models\Judiciary::find()->where(['contract_id' => $contract_id])->all();
                    if (!empty($judicaries)): ?>
                        <h4 style="margin-top:15px"><i class="fa fa-gavel"></i> المعلومات القضائية</h4>
                        <?php foreach ($judicaries as $jud): ?>
                            <div class="p-3 bg-light rounded small">
                                <strong>القضية:</strong> <?= $jud->judiciary_number ?>/<?= $jud->year ?><br>
                                <strong>تاريخ الورود:</strong> <?= $jud->income_date ?: 'لا يوجد' ?><br>
                                <?php $law = \backend\modules\lawyers\models\Lawyers::findOne($jud->lawyer_id); ?>
                                <?php if ($law): ?><strong>المحامي:</strong> <?= Html::encode($law->name) ?><br><?php endif ?>
                                <?php $court = \backend\modules\court\models\Court::findOne($jud->court_id); ?>
                                <?php if ($court): ?><strong>المحكمة:</strong> <?= Html::encode($court->name) ?><br><?php endif ?>
                            </div>
                        <?php endforeach ?>
                    <?php endif ?>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<!-- ═══ نافذة إضافة تسوية (محدّثة) ═══ -->
<?php
/* ── حساب إجمالي الدين تلقائياً — من vw_contract_balance ── */
$_vbStl = \backend\modules\followUp\helper\ContractCalculations::fromView($contractModel->id);
$_stlTotalDebt = $_vbStl ? $_vbStl['contractValue'] : (float)($contractModel->total_value ?? 0);
$_stlAutoTotal = $_vbStl ? $_vbStl['totalDebt'] : (float)($contractModel->total_value ?? 0);
$_stlPaidAmount = $_vbStl ? $_vbStl['paid'] : 0;
$_stlLawyerCost = $_vbStl ? $_vbStl['lawyerCost'] : 0;
$_stlAllExpenses = $_vbStl ? $_vbStl['expenses'] : 0;
$_stlNetDebt = $_vbStl ? $_vbStl['remaining'] : max(0, $_stlAutoTotal - $_stlPaidAmount);
?>

<style>
.stl-modal .form-group{margin-bottom:14px}
.stl-modal label{font-size:13px;font-weight:600;color:#555;margin-bottom:5px;display:block}
.stl-modal label .fa{margin-left:4px;color:#800020;font-size:11px}
.stl-modal .form-control{border-radius:6px;height:40px;font-size:13px;border:1.5px solid #ddd;transition:border-color .2s}
.stl-modal .form-control:focus{border-color:#800020;box-shadow:0 0 0 3px rgba(128,0,32,.08)}
.stl-modal .stl-section{font-size:11px;font-weight:700;color:#999;text-transform:uppercase;letter-spacing:.4px;margin:14px 0 10px;padding-bottom:5px;border-bottom:2px solid #f0f0f0}
.stl-modal .stl-type-toggle{display:flex;gap:6px;margin-bottom:14px}
.stl-modal .stl-type-btn{flex:1;padding:10px 12px;border:2px solid #e2e8f0;border-radius:8px;text-align:center;cursor:pointer;transition:all .2s;background:#f8f9fa;font-weight:600;font-size:12px}
.stl-modal .stl-type-btn:hover{border-color:#800020;background:#fff}
.stl-modal .stl-type-btn.active{border-color:#800020;background:#800020;color:#fff}
.stl-modal .stl-type-btn i{display:block;font-size:18px;margin-bottom:3px}
.stl-modal .stl-preview{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px;margin-top:10px}
.stl-modal .stl-preview-row{display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid #f0f0f0;font-size:12px}
.stl-modal .stl-preview-row:last-child{border-bottom:none}
.stl-modal .stl-preview-row .stl-lbl{color:#64748b}
.stl-modal .stl-preview-row .stl-val{font-weight:700;color:#1e293b}
.stl-modal .stl-amount{font-weight:600;text-align:center;font-size:15px!important}
.stl-modal .stl-debt-card{background:linear-gradient(135deg,#f0f4ff,#e8eeff);border:1px solid #c7d2fe;border-radius:8px;padding:14px;margin-bottom:14px}
.stl-modal .stl-debt-row{display:flex;justify-content:space-between;font-size:12px;padding:3px 0;color:#475569}
.stl-modal .stl-debt-row.stl-debt-total{border-top:2px solid #800020;margin-top:6px;padding-top:8px;font-size:14px;font-weight:700;color:#800020}
</style>

<div class="modal fade" id="settlementModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content stl-modal">
            <div class="modal-header" style="background:linear-gradient(135deg,#800020,#a0003a);color:#fff;border-radius:4px 4px 0 0">
                <button type="button" class="close" data-bs-dismiss="modal" style="color:#fff;opacity:.8"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-balance-scale"></i> إضافة تسوية</h4>
            </div>
            <div class="modal-body">
                <div class="alert loan-alert" style="display:none;border-radius:6px"></div>

                <!-- إجمالي الدين (محسوب تلقائياً) -->
                <div class="stl-debt-card">
                    <div class="stl-section" style="margin-top:0;border-bottom:none">إجمالي الدين</div>
                    <div class="stl-debt-row"><span>المبلغ الأصلي للعقد</span><span><?= number_format($_stlTotalDebt, 2) ?> د.أ</span></div>
                    <?php if ($_stlAllExpenses > 0): ?>
                    <div class="stl-debt-row"><span>إجمالي المصاريف (Outcome)</span><span><?= number_format($_stlAllExpenses, 2) ?> د.أ</span></div>
                    <?php endif ?>
                    <?php if ($_stlLawyerCost > 0): ?>
                    <div class="stl-debt-row"><span>أتعاب المحاماة</span><span><?= number_format($_stlLawyerCost, 2) ?> د.أ</span></div>
                    <?php endif ?>
                    <div class="stl-debt-row" style="border-top:1px solid #c7d2fe;margin-top:4px;padding-top:6px"><span>الإجمالي قبل الخصم</span><span><?= number_format($_stlAutoTotal, 2) ?> د.أ</span></div>
                    <div class="stl-debt-row" style="color:#059669"><span><i class="fa fa-check-circle"></i> المدفوع</span><span style="color:#059669">- <?= number_format($_stlPaidAmount, 2) ?> د.أ</span></div>
                    <div class="stl-debt-row stl-debt-total"><span>صافي الدين</span><span id="stl_total_display"><?= number_format($_stlNetDebt, 2) ?> د.أ</span></div>
                </div>
                <input type="hidden" id="stl_total_debt" value="<?= $_stlNetDebt ?>">

                <!-- نوع التسوية -->
                <div class="stl-section">نوع التسوية</div>
                <div class="stl-type-toggle">
                    <div class="stl-type-btn active" data-type="monthly" onclick="StlForm.setType('monthly')">
                        <i class="fa fa-calendar"></i> شهري
                    </div>
                    <div class="stl-type-btn" data-type="weekly" onclick="StlForm.setType('weekly')">
                        <i class="fa fa-calendar-o"></i> أسبوعي
                    </div>
                </div>
                <input type="hidden" id="stl_settlement_type" value="monthly">

                <!-- تفاصيل التسوية -->
                <div class="stl-section">تفاصيل التسوية</div>
                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label><i class="fa fa-money"></i> الدفعة الأولى (مبلغ ثابت)</label>
                            <input type="number" step="0.01" class="form-control stl-amount" id="stl_first_payment" placeholder="0.00" oninput="StlForm.calculate()">
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label><i class="fa fa-money"></i> <span id="stl_installment_label">القسط الشهري</span></label>
                            <input type="number" step="0.01" class="form-control stl-amount" id="monthly_installment" placeholder="0.00" oninput="StlForm.calculate()">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label><i class="fa fa-calendar"></i> تاريخ الدفعة الأولى للتسوية</label>
                            <input type="date" class="form-control" id="first_installment_date" onchange="StlForm.onFirstDateChange()">
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label><i class="fa fa-calendar-plus-o"></i> تاريخ القسط الجديد</label>
                            <input type="date" class="form-control" id="new_installment_date" onchange="StlForm.validateNewDate()">
                            <span class="help-block" id="stl_date_error" style="display:none;color:#e74c3c;font-size:11px"></span>
                        </div>
                    </div>
                </div>

                <!-- معاينة الجدولة -->
                <div class="stl-preview" id="stl_preview_box" style="display:none">
                    <div class="stl-section" style="border-bottom:none;margin:0 0 6px">معاينة الجدولة</div>
                    <div class="stl-preview-row"><span class="stl-lbl">إجمالي الدين</span><span class="stl-val" id="stl_p_debt">—</span></div>
                    <div class="stl-preview-row"><span class="stl-lbl">الدفعة الأولى</span><span class="stl-val" id="stl_p_fp">—</span></div>
                    <div class="stl-preview-row"><span class="stl-lbl">المبلغ المتبقي بعد الدفعة</span><span class="stl-val" id="stl_p_after_fp">—</span></div>
                    <div class="stl-preview-row"><span class="stl-lbl">قيمة القسط</span><span class="stl-val" id="stl_p_inst">—</span></div>
                    <div class="stl-preview-row"><span class="stl-lbl">عدد الأقساط</span><span class="stl-val" id="stl_p_count">—</span></div>
                    <div class="stl-preview-row"><span class="stl-lbl">آخر قسط (تقريبي)</span><span class="stl-val" id="stl_p_last">—</span></div>
                    <div class="stl-preview-row"><span class="stl-lbl">المستحق الكلي (دفعة + أقساط)</span><span class="stl-val" id="stl_p_total_due">—</span></div>
                </div>

                <input type="hidden" id="stl_installments_count" value="">
                <input type="hidden" id="stl_remaining_debt" value="">

                <!-- ملاحظات -->
                <div class="form-group" style="margin-top:12px">
                    <label><i class="fa fa-sticky-note-o"></i> ملاحظات</label>
                    <textarea class="form-control" id="stl_notes" rows="2" placeholder="ملاحظات إضافية (اختياري)..." style="height:auto;border-radius:6px"></textarea>
                </div>

                <input type="hidden" value="<?= $contractModel->id ?>" id="contract_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa fa-times"></i> إلغاء</button>
                <button type="button" class="btn btn-primary" id="save" style="background:#800020;border-color:#800020">
                    <i class="fa fa-plus-circle"></i> إنشاء التسوية
                </button>
            </div>
        </div>
    </div>
</div>

<script>
var StlForm = (function(){
    function setType(type) {
        document.getElementById('stl_settlement_type').value = type;
        document.querySelectorAll('.stl-type-btn').forEach(function(btn){
            btn.classList.toggle('active', btn.getAttribute('data-type') === type);
        });
        document.getElementById('stl_installment_label').textContent = type === 'weekly' ? 'القسط الأسبوعي' : 'القسط الشهري';
        calculate();
    }
    function calculate() {
        var totalDebt = parseFloat(document.getElementById('stl_total_debt').value) || 0;
        var fp   = parseFloat(document.getElementById('stl_first_payment').value) || 0;
        var inst = parseFloat(document.getElementById('monthly_installment').value) || 0;
        var box  = document.getElementById('stl_preview_box');
        var afterFp = Math.max(0, totalDebt - fp);

        if (inst > 0 && afterFp > 0) {
            var count = Math.ceil(afterFp / inst);
            var type = document.getElementById('stl_settlement_type').value;
            var firstEl = document.getElementById('first_installment_date');
            var lastDate = '—';
            if (firstEl && firstEl.value) {
                var d = new Date(firstEl.value);
                if (type === 'weekly') d.setDate(d.getDate() + (count - 1) * 7);
                else d.setMonth(d.getMonth() + (count - 1));
                lastDate = d.toISOString().split('T')[0];
            }
            var totalDue = fp + (count * inst);

            document.getElementById('stl_p_debt').textContent = totalDebt.toLocaleString('ar-JO') + ' د.أ';
            document.getElementById('stl_p_fp').textContent = fp > 0 ? fp.toLocaleString('ar-JO') + ' د.أ' : 'لا يوجد';
            document.getElementById('stl_p_after_fp').textContent = afterFp.toLocaleString('ar-JO') + ' د.أ';
            document.getElementById('stl_p_inst').textContent = inst.toLocaleString('ar-JO') + ' د.أ ' + (type === 'weekly' ? '(أسبوعي)' : '(شهري)');
            document.getElementById('stl_p_count').textContent = count + ' قسط';
            document.getElementById('stl_p_last').textContent = lastDate;
            document.getElementById('stl_p_total_due').textContent = totalDue.toLocaleString('ar-JO') + ' د.أ';

            document.getElementById('stl_installments_count').value = count;
            document.getElementById('stl_remaining_debt').value = Math.max(0, afterFp - count * inst);
            box.style.display = 'block';
        } else if (fp > 0 && inst <= 0) {
            // فقط دفعة أولى بدون أقساط
            document.getElementById('stl_p_debt').textContent = totalDebt.toLocaleString('ar-JO') + ' د.أ';
            document.getElementById('stl_p_fp').textContent = fp.toLocaleString('ar-JO') + ' د.أ';
            document.getElementById('stl_p_after_fp').textContent = afterFp.toLocaleString('ar-JO') + ' د.أ';
            document.getElementById('stl_p_inst').textContent = '—';
            document.getElementById('stl_p_count').textContent = '—';
            document.getElementById('stl_p_last').textContent = '—';
            document.getElementById('stl_p_total_due').textContent = fp.toLocaleString('ar-JO') + ' د.أ';
            document.getElementById('stl_installments_count').value = 0;
            document.getElementById('stl_remaining_debt').value = afterFp;
            box.style.display = 'block';
        } else {
            box.style.display = 'none';
        }
    }
    function onFirstDateChange() {
        var firstEl = document.getElementById('first_installment_date');
        var newEl = document.getElementById('new_installment_date');
        if (firstEl.value) {
            var type = document.getElementById('stl_settlement_type').value;
            var d = new Date(firstEl.value);
            // اقتراح تاريخ القسط الجديد: أسبوع بعد الدفعة الأولى أو شهر حسب النوع
            if (type === 'weekly') {
                d.setDate(d.getDate() + 7);
            } else {
                d.setMonth(d.getMonth() + 1);
            }
            newEl.value = d.toISOString().split('T')[0];
            // تحديد الحد الأدنى: أسبوع بعد الدفعة الأولى
            var minDate = new Date(firstEl.value);
            minDate.setDate(minDate.getDate() + 7);
            newEl.min = minDate.toISOString().split('T')[0];
        }
        calculate();
        validateNewDate();
    }

    function validateNewDate() {
        var firstEl = document.getElementById('first_installment_date');
        var newEl = document.getElementById('new_installment_date');
        var errEl = document.getElementById('stl_date_error');
        if (!firstEl.value || !newEl.value) {
            errEl.style.display = 'none';
            return true;
        }
        var firstDate = new Date(firstEl.value);
        var newDate = new Date(newEl.value);
        var minDate = new Date(firstEl.value);
        minDate.setDate(minDate.getDate() + 7);

        if (newDate <= firstDate) {
            errEl.textContent = 'يجب أن يكون تاريخ القسط الجديد بعد تاريخ الدفعة الأولى';
            errEl.style.display = 'block';
            newEl.style.borderColor = '#e74c3c';
            return false;
        }
        if (newDate < minDate) {
            errEl.textContent = 'يجب أن يكون تاريخ القسط الجديد بعد الدفعة الأولى بأسبوع على الأقل';
            errEl.style.display = 'block';
            newEl.style.borderColor = '#e74c3c';
            return false;
        }
        errEl.style.display = 'none';
        newEl.style.borderColor = '#ddd';
        return true;
    }

    return { setType: setType, calculate: calculate, onFirstDateChange: onFirstDateChange, validateNewDate: validateNewDate };
})();
</script>
