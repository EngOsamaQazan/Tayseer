/**
 * OCP — Operational Control Panel JavaScript
 * ===========================================
 * Handles: Side Panels, Kanban Drag&Drop, Timeline, AJAX, Keyboard Shortcuts
 */

(function () {
    'use strict';

    var config = window.OCP_CONFIG || {};
    var currentPanel = null;
    var draggedTaskId = null;
    var draggedSourceStage = null;
    var pendingKanbanMove = null;

    window.OCP = {

        // ═══════════════════════════════════════
        // SIDE PANEL
        // ═══════════════════════════════════════
        openPanel: function (type) {
            this.closePanel();
            var panel = document.getElementById('panel-' + type);
            var overlay = document.getElementById('ocp-overlay');
            if (panel) {
                panel.classList.add('open');
                overlay.classList.add('open');
                currentPanel = type;
                // Focus first input
                setTimeout(function () {
                    var firstInput = panel.querySelector('input:not([type=hidden]), textarea, select');
                    if (firstInput) firstInput.focus();
                }, 300);
            }
        },

        closePanel: function () {
            var panels = document.querySelectorAll('.ocp-side-panel.open');
            var overlay = document.getElementById('ocp-overlay');
            panels.forEach(function (p) { p.classList.remove('open'); });
            if (overlay) overlay.classList.remove('open');
            currentPanel = null;
        },

        // ═══════════════════════════════════════
        // TABS
        // ═══════════════════════════════════════
        switchTab: function (tabName) {
            document.querySelectorAll('.ocp-tab').forEach(function (t) {
                t.classList.toggle('active', t.dataset.tab === tabName);
            });
            document.querySelectorAll('.ocp-tab-content').forEach(function (c) {
                c.classList.toggle('ocp-hidden', c.id !== 'tab-' + tabName);
            });
            try { sessionStorage.setItem('ocp_active_tab', tabName); } catch(e) {}
        },

        getActiveTab: function () {
            try { return sessionStorage.getItem('ocp_active_tab') || 'timeline'; } catch(e) { return 'timeline'; }
        },

        restoreTab: function () {
            var tab = this.getActiveTab();
            if (tab && tab !== 'timeline') this.switchTab(tab);
        },

        // ═══════════════════════════════════════
        // ACTION CENTER
        // ═══════════════════════════════════════
        toggleMoreActions: function () {
            var el = document.getElementById('ocp-more-actions');
            if (el) {
                el.classList.toggle('ocp-hidden');
            }
        },

        // ═══════════════════════════════════════
        // TIMELINE
        // ═══════════════════════════════════════
        filterTimeline: function (type) {
            document.querySelectorAll('.ocp-timeline__filter-btn').forEach(function (b) {
                b.classList.toggle('active', b.dataset.filter === type);
            });
            document.querySelectorAll('.ocp-timeline-event').forEach(function (e) {
                if (type === 'all') {
                    e.style.display = '';
                } else {
                    e.style.display = e.dataset.eventType === type ? '' : 'none';
                }
            });
            this.checkTimelineOverflow();
        },

        toggleEventExpand: function (index) {
            var el = document.getElementById('event-content-' + index);
            if (el) {
                el.classList.toggle('ocp-timeline-event__content--collapsed');
                var btn = el.nextElementSibling;
                if (btn && btn.classList.contains('ocp-timeline-event__expand')) {
                    btn.textContent = el.classList.contains('ocp-timeline-event__content--collapsed') ? 'عرض المزيد' : 'عرض أقل';
                }
            }
        },

        scrollTimelineToBottom: function () {
            var list = document.getElementById('ocp-timeline-list');
            if (list) {
                list.scrollTop = 0;
            }
        },

        checkTimelineOverflow: function () {
            document.querySelectorAll('.ocp-timeline-event__content--collapsed').forEach(function (el) {
                var btn = el.nextElementSibling;
                if (btn && btn.classList.contains('ocp-timeline-event__expand')) {
                    btn.style.display = (el.scrollHeight > el.clientHeight) ? '' : 'none';
                }
            });
        },

        // ═══════════════════════════════════════
        // KANBAN DRAG & DROP
        // ═══════════════════════════════════════
        kanbanDragStart: function (e) {
            var card = e.target.closest('.ocp-kanban-card');
            if (!card) return;
            draggedTaskId = card.dataset.taskId;
            draggedSourceStage = card.dataset.stage;
            card.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', draggedTaskId);
        },

        kanbanDragOver: function (e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            var body = e.target.closest('.ocp-kanban__column-body');
            if (body) body.classList.add('drag-over');
        },

        kanbanDragLeave: function (e) {
            var body = e.target.closest('.ocp-kanban__column-body');
            if (body) body.classList.remove('drag-over');
        },

        kanbanDrop: function (e) {
            e.preventDefault();
            var body = e.target.closest('.ocp-kanban__column-body');
            if (!body) return;
            body.classList.remove('drag-over');

            var targetStage = body.dataset.stage;
            if (!draggedTaskId || targetStage === draggedSourceStage) {
                this._cleanupDrag();
                return;
            }

            // Governance: escalation/legal requires reason
            if (targetStage === 'escalation' || targetStage === 'legal') {
                pendingKanbanMove = {
                    taskId: draggedTaskId,
                    targetStage: targetStage,
                    sourceStage: draggedSourceStage
                };
                document.getElementById('escalation-task-id').value = draggedTaskId;
                document.getElementById('escalation-target-stage').value = targetStage;
                this.openPanel('escalation-reason');
                this._cleanupDrag();
                return;
            }

            // Governance: promise requires promise form
            if (targetStage === 'promise') {
                pendingKanbanMove = {
                    taskId: draggedTaskId,
                    targetStage: targetStage,
                    sourceStage: draggedSourceStage
                };
                this.openPanel('promise');
                this._cleanupDrag();
                return;
            }

            // Normal move
            this._moveTask(draggedTaskId, targetStage);
            this._cleanupDrag();
        },

        _cleanupDrag: function () {
            document.querySelectorAll('.ocp-kanban-card.dragging').forEach(function (c) {
                c.classList.remove('dragging');
            });
            draggedTaskId = null;
            draggedSourceStage = null;
        },

        _moveTask: function (taskId, targetStage, extraData) {
            var self = this;
            var data = $.extend({
                task_id: taskId,
                target_stage: targetStage,
                contract_id: config.contractId
            }, extraData || {});

            $.ajax({
                url: config.urls.moveTask,
                method: 'POST',
                data: data,
                dataType: 'json',
                success: function (res) {
                    if (res.success) {
                        self.toast('تم نقل المهمة بنجاح', 'success');
                        self._moveCardInDOM(taskId, targetStage);
                    } else {
                        self.toast(res.message || 'حدث خطأ أثناء نقل المهمة', 'error');
                    }
                },
                error: function () {
                    self.toast('خطأ في الاتصال بالسيرفر', 'error');
                }
            });
        },

        _moveCardInDOM: function (taskId, targetStage) {
            var card = document.querySelector('.ocp-kanban-card[data-task-id="' + taskId + '"]');
            var targetBody = document.querySelector('.ocp-kanban__column-body[data-stage="' + targetStage + '"]');
            if (card && targetBody) {
                var addBtn = targetBody.querySelector('.ocp-kanban__add-task');
                card.dataset.stage = targetStage;
                targetBody.insertBefore(card, addBtn);
                // Update column stats (simplified — in production, re-render)
            }
        },

        confirmKanbanEscalation: function () {
            if (!pendingKanbanMove) return;
            var form = document.getElementById('form-escalation-reason');
            var reason = form.querySelector('[name=reason]').value;
            var type = form.querySelector('[name=type]').value;

            if (!reason || !type) {
                this.toast('يجب تعبئة جميع الحقول الإلزامية', 'error');
                return;
            }

            this._moveTask(pendingKanbanMove.taskId, pendingKanbanMove.targetStage, {
                escalation_reason: reason,
                escalation_type: type
            });
            this.closePanel();
            pendingKanbanMove = null;
        },

        cancelKanbanMove: function () {
            pendingKanbanMove = null;
            this.closePanel();
        },

        quickCreateTask: function (stage) {
            document.getElementById('task-stage').value = stage;
            this.openPanel('create-task');
        },

        // ═══════════════════════════════════════
        // FORM SUBMISSIONS
        // ═══════════════════════════════════════
        submitFollowUp: function (e, type) {
            e.preventDefault();
            var self = this;
            var form = e.target;
            var formData = $(form).serialize();

            $.ajax({
                url: config.urls.saveFollowUp,
                method: 'POST',
                data: formData,
                dataType: 'json',
                beforeSend: function () {
                    $(form).find('button[type=submit]').prop('disabled', true);
                },
                success: function (res) {
                    if (res.success) {
                        self.toast(res.message || 'تم الحفظ بنجاح', 'success');
                        self.closePanel();
                        form.reset();
                        // Refresh timeline
                        self.refreshTimeline();
                    } else {
                        self.toast(res.message || 'حدث خطأ', 'error');
                    }
                },
                error: function () {
                    self.toast('خطأ في الاتصال بالسيرفر', 'error');
                },
                complete: function () {
                    $(form).find('button[type=submit]').prop('disabled', false);
                }
            });
            return false;
        },

        submitSms: function (e) {
            e.preventDefault();
            var self = this;
            var form = e.target;

            $.ajax({
                url: config.urls.sendSms,
                method: 'POST',
                data: $(form).serialize(),
                dataType: 'json',
                success: function (res) {
                    if (res.success) {
                        self.toast('تم إرسال الرسالة بنجاح', 'success');
                        self.closePanel();
                    } else {
                        self.toast(res.message || 'فشل إرسال الرسالة', 'error');
                    }
                },
                error: function () {
                    self.toast('خطأ في الاتصال', 'error');
                }
            });
            return false;
        },

        submitEscalation: function (e) {
            e.preventDefault();
            var self = this;
            var form = e.target;

            $.ajax({
                url: config.urls.saveFollowUp,
                method: 'POST',
                data: $(form).serialize() + '&action_type=escalation&feeling=تصعيد&connection_goal=1&reminder=' + self._dateOffset(1),
                dataType: 'json',
                success: function (res) {
                    if (res.success) {
                        self.toast('تم التصعيد بنجاح', 'success');
                        self.closePanel();
                        self.refreshTimeline();
                    } else {
                        self.toast(res.message || 'حدث خطأ', 'error');
                    }
                },
                error: function () {
                    self.toast('خطأ في الاتصال', 'error');
                }
            });
            return false;
        },

        submitFreeze: function (e) {
            e.preventDefault();
            var self = this;
            var form = e.target;

            $.ajax({
                url: config.urls.saveFollowUp,
                method: 'POST',
                data: $(form).serialize() + '&action_type=freeze&feeling=تجميد&connection_goal=3&reminder=' + self._dateOffset(30) + '&notes=' + encodeURIComponent(form.querySelector('[name=reason]').value),
                dataType: 'json',
                success: function (res) {
                    if (res.success) {
                        self.toast('تم تجميد المتابعة', 'success');
                        self.closePanel();
                    } else {
                        self.toast(res.message || 'حدث خطأ', 'error');
                    }
                },
                error: function () {
                    self.toast('خطأ في الاتصال', 'error');
                }
            });
            return false;
        },

        submitTask: function (e) {
            e.preventDefault();
            var self = this;
            var form = e.target;

            $.ajax({
                url: config.urls.createTask,
                method: 'POST',
                data: $(form).serialize(),
                dataType: 'json',
                success: function (res) {
                    if (res.success) {
                        self.toast('تم إنشاء المهمة بنجاح', 'success');
                        self.closePanel();
                        form.reset();
                        // Add card to Kanban
                        if (res.task) {
                            self._addKanbanCard(res.task);
                        } else {
                            location.reload();
                        }
                    } else {
                        self.toast(res.message || 'حدث خطأ', 'error');
                    }
                },
                error: function () {
                    self.toast('خطأ في الاتصال', 'error');
                }
            });
            return false;
        },

        // ═══════════════════════════════════════
        // AI
        // ═══════════════════════════════════════
        executeAIAction: function (actionType) {
            if (actionType === 'add_judiciary_action' || actionType === 'open_case') {
                var config = window.OCP_CONFIG || {};
                if (config.urls && config.urls.createJudiciary) {
                    window.open(config.urls.createJudiciary, '_blank');
                }
                return;
            }

            // Map AI action to panel opening
            var panelMap = {
                'call': 'call',
                'promise': 'promise',
                'visit': 'visit',
                'sms': 'sms',
                'escalate': 'legal',
                'legal_review': 'legal',
                'review': 'review',
                'note': 'note'
            };
            var panel = panelMap[actionType];
            if (panel) {
                this.openPanel(panel);
            }
        },

        aiFeedback: function (feedback) {
            var self = this;
            // Visual feedback
            document.querySelectorAll('.ocp-ai-feedback__btn').forEach(function (b) {
                b.classList.toggle('selected', b.dataset.feedback === feedback);
            });

            $.ajax({
                url: config.urls.aiFeedback,
                method: 'POST',
                data: {
                    contract_id: config.contractId,
                    feedback: feedback
                },
                dataType: 'json',
                success: function (res) {
                    self.toast('شكراً لتقييمك', 'info');
                }
            });
        },

        handleAlertCTA: function (btn) {
            var action = btn.dataset.action;
            if (!action) return;

            if (action === 'add_judiciary_action') {
                var config = window.OCP_CONFIG || {};
                if (config.urls && config.urls.createJudiciary) {
                    window.open(config.urls.createJudiciary, '_blank');
                }
                return;
            }

            this.openPanel(action);
        },

        // ═══════════════════════════════════════
        // TIMELINE REFRESH
        // ═══════════════════════════════════════
        refreshTimeline: function () {
            var self = this;
            if (!config.urls.getTimeline) return;

            $.get(config.urls.getTimeline, function (html) {
                var container = document.getElementById('tab-timeline');
                if (container) {
                    container.innerHTML = html;
                    var events = container.querySelectorAll('.ocp-timeline-event');
                    var countBadge = document.querySelector('.ocp-tab[data-tab="timeline"] .ocp-tab__count');
                    if (countBadge) countBadge.textContent = events.length;
                    OCP.checkTimelineOverflow();
                }
            });
        },

        // ═══════════════════════════════════════
        // DOM HELPERS
        // ═══════════════════════════════════════
        _addKanbanCard: function (task) {
            var body = document.querySelector('.ocp-kanban__column-body[data-stage="' + task.stage + '"]');
            if (!body) return;

            var addBtn = body.querySelector('.ocp-kanban__add-task');
            var card = document.createElement('div');
            card.className = 'ocp-kanban-card' + (task.is_overdue ? ' ocp-kanban-card--overdue' : '');
            card.draggable = true;
            card.dataset.taskId = task.id;
            card.dataset.stage = task.stage;
            card.setAttribute('ondragstart', 'OCP.kanbanDragStart(event)');
            card.innerHTML = '<div class="ocp-kanban-card__title">' + this._escapeHtml(task.title) + '</div>' +
                '<div class="ocp-kanban-card__meta">' +
                '<span class="ocp-kanban-card__due">' + (task.due_date || '') + '</span>' +
                '</div>';

            body.insertBefore(card, addBtn);
        },

        _escapeHtml: function (str) {
            var div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        },

        _dateOffset: function (days) {
            var d = new Date();
            d.setDate(d.getDate() + days);
            return d.toISOString().split('T')[0];
        },

        // ═══════════════════════════════════════
        // TOAST
        // ═══════════════════════════════════════
        toast: function (message, type) {
            type = type || 'info';
            var toast = document.getElementById('ocp-toast');
            var icon = document.getElementById('ocp-toast-icon');
            var msg = document.getElementById('ocp-toast-message');

            if (!toast) return;

            toast.className = 'ocp-toast ocp-toast--' + type;
            icon.className = 'fa ' + (type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle');
            icon.style.color = type === 'success' ? 'var(--ocp-success)' : type === 'error' ? 'var(--ocp-danger)' : 'var(--ocp-info)';
            msg.textContent = message;

            // Show
            setTimeout(function () { toast.classList.add('show'); }, 10);
            // Hide
            setTimeout(function () { toast.classList.remove('show'); }, 3500);
        },

        // ═══════════════════════════════════════
        // COPY
        // ═══════════════════════════════════════
        copyToClipboard: function (text) {
            var self = this;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function () {
                    self.toast('تم نسخ رقم العقد', 'success');
                });
            } else {
                var ta = document.createElement('textarea');
                ta.value = text;
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
                self.toast('تم نسخ رقم العقد', 'success');
            }
        },

        // ═══════════════════════════════════════
        // KEYBOARD SHORTCUTS
        // ═══════════════════════════════════════
        _initKeyboardShortcuts: function () {
            var self = this;
            document.addEventListener('keydown', function (e) {
                // Don't trigger when typing in inputs
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return;
                // Escape closes panel
                if (e.key === 'Escape') {
                    self.closePanel();
                    return;
                }
                // Shortcuts: C=Call, P=Promise, V=Visit, S=SMS, L=Legal
                var shortcutMap = {
                    'c': 'call',
                    'p': 'promise',
                    'v': 'visit',
                    's': 'sms',
                    'l': 'legal'
                };
                var lower = e.key.toLowerCase();
                if (shortcutMap[lower] && !e.ctrlKey && !e.altKey && !e.metaKey) {
                    e.preventDefault();
                    self.openPanel(shortcutMap[lower]);
                }
            });
        },

        // ═══════════════════════════════════════
        // MOBILE: COLLAPSIBLE SECTIONS
        // ═══════════════════════════════════════
        _initMobileAccordion: function () {
            if (window.innerWidth > 480) return;
            document.querySelectorAll('.ocp-section--collapsible .ocp-section-title').forEach(function (title) {
                title.addEventListener('click', function () {
                    this.closest('.ocp-section--collapsible').classList.toggle('collapsed');
                });
            });
        },

        // ═══════════════════════════════════════
        // JUDICIARY CHECK DATE UPDATE
        // ═══════════════════════════════════════
        updateJudiciaryCheck: function (judiciaryId) {
            if (!OCP_CONFIG || !OCP_CONFIG.urls || !OCP_CONFIG.urls.updateJudiciaryCheck) {
                this.toast('رابط تحديث التشييك غير متاح', 'warning');
                return;
            }
            var self = this;
            $.post(OCP_CONFIG.urls.updateJudiciaryCheck, {
                judiciary_id: judiciaryId
            }, function (res) {
                if (res.success) {
                    self.toast('تم تحديث تاريخ التشييك: ' + res.date, 'success');
                    // Reload page to reflect change
                    setTimeout(function () { location.reload(); }, 800);
                } else {
                    self.toast(res.message || 'حدث خطأ', 'error');
                }
            }).fail(function () {
                self.toast('خطأ في الاتصال بالخادم', 'error');
            });
        },

        // ═══════════════════════════════════════
        // INIT
        // ═══════════════════════════════════════
        init: function () {
            this._initKeyboardShortcuts();
            this._initMobileAccordion();
            this.scrollTimelineToBottom();
            this.checkTimelineOverflow();
        }
    };

    // Auto-init on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { OCP.init(); });
    } else {
        OCP.init();
    }

})();
