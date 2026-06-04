(function () {
    'use strict';

    if (typeof Garnish === 'undefined' || typeof Craft === 'undefined') {
        return;
    }

    var MatrixBlockAnchorClipboard = Garnish.Base.extend({
        _isProcessing: false,

        init: function () {
            this.addListener(Garnish.$doc, 'click', '_onClick');
        },

        _onClick: function (e) {
            var btn = e.target.closest('.clip-copy');
            if (!btn || this._isProcessing) {
                return;
            }

            this._isProcessing = true;
            this._copy(btn);

            var self = this;
            setTimeout(function () {
                self._isProcessing = false;
            }, 1000);
        },

        _copy: function (el) {
            var input =
                (el.previousElementSibling && el.previousElementSibling.querySelector('input')) ||
                el.previousElementSibling;

            if (!input || !input.value) {
                Craft.cp.displayError(Craft.t('matrix-block-anchor', 'No anchor text found to copy.'));
                this._isProcessing = false;
                return;
            }

            navigator.clipboard
                .writeText(input.value)
                .then(function () {
                    el.classList.add('success');
                    el.setAttribute('data-message', Craft.t('matrix-block-anchor', 'copied'));
                    setTimeout(function () {
                        el.classList.remove('success');
                        el.removeAttribute('data-message');
                    }, 1000);
                })
                .catch(function () {
                    el.classList.add('error');
                    el.setAttribute('data-message', Craft.t('matrix-block-anchor', 'error'));
                    setTimeout(function () {
                        el.classList.remove('error');
                        el.removeAttribute('data-message');
                    }, 1000);
                    Craft.cp.displayError(Craft.t('matrix-block-anchor', 'Failed to copy anchor to clipboard.'));
                });
        },

        destroy: function () {
            this.base();
        },
    });

    new MatrixBlockAnchorClipboard();
})();
