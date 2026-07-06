(function () {
    'use strict';

    if (typeof Garnish === 'undefined' || typeof Craft === 'undefined') {
        return;
    }

    var MatrixBlockAnchorClipboard = Garnish.Base.extend({
        // Buttons currently within their post-click debounce window. Scoped
        // per element so clicking one block's button never blocks another's.
        _processing: null,

        // Shared visually-hidden aria-live region, injected once and reused by
        // every copy button on the page via delegated events.
        _liveRegion: null,

        init: function () {
            this._processing = new WeakSet();
            this._liveRegion = this._createLiveRegion();
            this.addListener(Garnish.$doc, 'click', '_onClick');
        },

        _createLiveRegion: function () {
            var region = document.createElement('div');
            region.className = 'visually-hidden mba-live-region';
            region.setAttribute('role', 'status');
            region.setAttribute('aria-live', 'polite');
            region.setAttribute('aria-atomic', 'true');
            document.body.appendChild(region);
            return region;
        },

        _announce: function (message, assertive) {
            if (!this._liveRegion) {
                return;
            }

            this._liveRegion.setAttribute('aria-live', assertive ? 'assertive' : 'polite');
            // Clear first so identical consecutive messages are re-announced.
            this._liveRegion.textContent = '';
            var region = this._liveRegion;
            setTimeout(function () {
                region.textContent = message;
            }, 50);
        },

        _onClick: function (e) {
            var btn = e.target.closest('.clip-copy');
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

        _resolveInput: function (btn) {
            var targetId = btn.getAttribute('data-target');
            if (targetId) {
                var byId = document.getElementById(targetId);
                if (byId) {
                    return byId;
                }
            }

            var container = btn.closest('.copytext');
            return container ? container.querySelector('input') : null;
        },

        _copy: function (btn) {
            var input = this._resolveInput(btn);

            if (!input || !input.value) {
                var noText = Craft.t('matrix-block-anchor', 'No anchor text found to copy.');
                Craft.cp.displayError(noText);
                this._announce(noText, true);
                this._processing.delete(btn);
                return;
            }

            var self = this;

            navigator.clipboard
                .writeText(input.value)
                .then(function () {
                    var message = Craft.t('matrix-block-anchor', 'copied');
                    btn.classList.add('success');
                    btn.setAttribute('data-message', message);
                    self._announce(message, false);
                    setTimeout(function () {
                        btn.classList.remove('success');
                        btn.removeAttribute('data-message');
                    }, 1000);
                })
                .catch(function () {
                    var message = Craft.t('matrix-block-anchor', 'error');
                    var failure = Craft.t('matrix-block-anchor', 'Failed to copy anchor to clipboard.');
                    btn.classList.add('error');
                    btn.setAttribute('data-message', message);
                    self._announce(failure, true);
                    setTimeout(function () {
                        btn.classList.remove('error');
                        btn.removeAttribute('data-message');
                    }, 1000);
                    Craft.cp.displayError(failure);
                });
        },

        destroy: function () {
            if (this._liveRegion && this._liveRegion.parentNode) {
                this._liveRegion.parentNode.removeChild(this._liveRegion);
            }
            this.base();
        },
    });

    new MatrixBlockAnchorClipboard();
})();
