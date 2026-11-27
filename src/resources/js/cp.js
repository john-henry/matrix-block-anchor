(function () {
    'use strict';

    if (typeof Garnish === 'undefined' || typeof Craft === 'undefined') {
        return;
    }

    var MatrixBlockAnchorClipboard = Garnish.Base.extend({
        // Buttons currently within their post-click debounce window. Scoped
        // per element so clicking one block's button never blocks another's.
        _processing: null,

        init: function () {
            this._processing = new WeakSet();
            this.addListener(Garnish.$doc, 'click', '_onClick');
            this.addListener(Garnish.$doc, 'input', '_onInput');
        },

        // On Craft 6 an editable anchor has Craft's own copy button beside it,
        // which copies its `value` rather than reading the input.
        _onInput: function (e) {
            var field = e.target.closest ? e.target.closest('craft-input') : null;
            var copyButton = field ? field.querySelector('craft-copy-button[data-mba-copy]') : null;
            if (copyButton) {
                copyButton.value = e.target.value;
            }
        },

        _onClick: function (e) {
            var btn = e.target.closest('.mba-copy');
            if (!btn || this._processing.has(btn)) {
                return;
            }

            this._processing.add(btn);
            this._copy(btn);

            var self = this;
            setTimeout(function () {
                self._processing.delete(btn);
            }, 1000);
        },

        // The button's own wrapper is searched first. Looking the input up by ID
        // alone isn't safe: Craft 6 can give every block's input the same ID, and
        // the first match would belong to another block.
        _resolveInput: function (btn) {
            var container = btn.closest('.mba-copytext');
            var input = container ? container.querySelector('input') : null;
            if (input) {
                return input;
            }

            var targetId = btn.getAttribute('data-target');
            return targetId ? document.getElementById(targetId) : null;
        },

        _copy: function (btn) {
            var input = this._resolveInput(btn);

            // Craft's notifications sit in a status region, so these notices
            // reach screen readers without a live region of the plugin's own.
            if (!input || !input.value) {
                Craft.cp.displayError(Craft.t('matrix-block-anchor', 'No anchor text found to copy.'));
                this._processing.delete(btn);
                return;
            }

            this._writeText(input)
                .then(function () {
                    Craft.cp.displayNotice(Craft.t('app', 'Copied to clipboard.'));
                })
                .catch(function () {
                    Craft.cp.displayError(Craft.t('matrix-block-anchor', 'Failed to copy anchor to clipboard.'));
                });
        },

        // The Clipboard API only exists in a secure context, so a CP served over
        // plain http falls back to selecting the input and copying the selection.
        _writeText: function (input) {
            if (navigator.clipboard && window.isSecureContext) {
                return navigator.clipboard.writeText(input.value);
            }

            return new Promise(function (resolve, reject) {
                var active = document.activeElement;
                input.focus();
                input.select();
                var copied;
                try {
                    copied = document.execCommand('copy');
                } catch (e) {
                    copied = false;
                }
                input.setSelectionRange(0, 0);
                if (active && active.focus) {
                    active.focus();
                }
                if (copied) {
                    resolve();
                } else {
                    reject();
                }
            });
        },
    });

    new MatrixBlockAnchorClipboard();
})();
