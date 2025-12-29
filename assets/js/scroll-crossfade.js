(function ($) {
    "use strict";

    /**
     * Initialize a single widget instance.
     * $scope is the Elementor widget wrapper.
     */
    var initScrollCrossfade = function ($scope) {
        var $container = $scope.find('.scg-scroll-crossfade-gallery');
        if (!$container.length) return;

        var container = $container[0];
        var wrapper   = container.parentElement;
        var framesNodeList = container.querySelectorAll('.scg-frame');
        if (!framesNodeList.length) return;

        var frames = Array.prototype.slice.call(framesNodeList);

        var endPointRatio = parseFloat(container.dataset.endPoint);
        if (isNaN(endPointRatio)) {
            endPointRatio = 0.3; // default: 30% of viewport height
        }

        var startMode = container.dataset.startMode || 'fully_visible_bottom';

        var ensureAllFrames = container.dataset.ensureAllFrames === 'yes';
        var animationSpeedSeconds = parseFloat(container.dataset.animationSpeed);
        if (isNaN(animationSpeedSeconds) || animationSpeedSeconds <= 0) {
            animationSpeedSeconds = 1.5;
        }

        var scrollSmoothing = container.dataset.scrollSmoothing || 'none'; // none, light, strong
        var mobilePerformance = container.dataset.mobilePerformance === 'yes';

        var isMobile = window.matchMedia && window.matchMedia('(max-width: 767px)').matches;

        // Mobile performance simplifications
        if (mobilePerformance && isMobile) {
            // Disable animated catch-up on mobile for performance
            ensureAllFrames = false;
            // Slightly shorten CSS transitions
            frames.forEach(function (frame) {
                frame.style.transition = "opacity 0.1s linear";
            });
        }

        // --- ENFORCE LAYOUT IN JS (avoids CSS conflicts) ---
        container.style.position = container.style.position || 'relative';
        container.style.overflow = container.style.overflow || 'hidden';
        container.style.display  = container.style.display  || 'block';
        container.style.width    = container.style.width    || '100%';

        frames.forEach(function (frame, i) {
            frame.style.margin = "0";
            frame.style.padding = "0";
            if (i === 0) {
                // base frame participates in layout
                frame.style.position = "relative";
                frame.style.top = "";
                frame.style.left = "";
                frame.style.right = "";
                frame.style.bottom = "";
                frame.style.zIndex = "1";
            } else {
                // all others overlap on top
                frame.style.position = "absolute";
                frame.style.top = "0";
                frame.style.left = "0";
                frame.style.right = "0";
                frame.style.bottom = "0";
                frame.style.zIndex = "2";
            }
        });

        // Initial visibility: first frame is fully visible; others hidden
        frames.forEach(function (frame, i) {
            if (!mobilePerformance || !isMobile) {
                frame.style.transition = frame.style.transition || "opacity 0.15s linear";
            }
            frame.style.opacity = (i === 0) ? "1" : "0";
        });

        var firstFrame = frames[0];
        var firstImg = firstFrame ? firstFrame.querySelector('img') : null;

        // --- Preloader handling ---
        var preloader = wrapper ? wrapper.querySelector('.scg-preloader') : null;
        if (preloader) {
            var imgs = [];
            frames.forEach(function (frame) {
                var img = frame.querySelector('img');
                if (img) {
                    imgs.push(img);
                }
            });

            var remaining = 0;
            imgs.forEach(function (img) {
                if (!img.complete) {
                    remaining++;
                    img.addEventListener('load', function () {
                        remaining--;
                        if (remaining <= 0) {
                            preloader.classList.add('scg-preloader-hidden');
                        }
                    });
                    img.addEventListener('error', function () {
                        remaining--;
                        if (remaining <= 0) {
                            preloader.classList.add('scg-preloader-hidden');
                        }
                    });
                }
            });

            if (remaining === 0) {
                // All already loaded
                preloader.classList.add('scg-preloader-hidden');
            }
        }

        function syncHeight() {
            if (!firstImg) return;
            var h = firstImg.offsetHeight;
            if (h && h > 0) {
                container.style.height = h + "px";
            }
        }

        if (firstImg) {
            if (firstImg.complete) {
                syncHeight();
            } else {
                firstImg.addEventListener('load', function () {
                    syncHeight();
                    updateTargetFromScroll();
                });
            }

            firstImg.style.display = firstImg.style.display || "block";
            firstImg.style.width = firstImg.style.width || "100%";
            firstImg.style.height = firstImg.style.height || "auto";
        }

        frames.forEach(function (frame) {
            var img = frame.querySelector('img');
            if (!img) return;
            img.style.display = img.style.display || "block";
            img.style.width = img.style.width || "100%";
            img.style.height = img.style.height || "auto";
        });

        // Progress state
        var targetProgress = 0;   // where scroll wants us to be (0..1)
        var animProgress   = 0;   // where the animation currently is (0..1)
        var animating      = false;
        var lastTime       = null;

        function computeScrollProgress() {
            var rect = container.getBoundingClientRect();
            var vh = window.innerHeight || document.documentElement.clientHeight;

            var imgHeight = (firstImg && firstImg.offsetHeight) ? firstImg.offsetHeight : rect.height;

            var start;
            if (startMode === "fully_visible_bottom" && imgHeight <= vh) {
                // start when image is fully visible and bottom touches viewport bottom
                start = vh - imgHeight;
            } else {
                // start when top hits bottom
                start = vh;
            }

            var end = vh * endPointRatio;

            var p;
            if (start === end) {
                p = (rect.top <= start) ? 1 : 0;
            } else {
                p = (start - rect.top) / (start - end);
            }

            if (p < 0) p = 0;
            if (p > 1) p = 1;
            return p;
        }

        function render(progress) {
            var total = frames.length;

            if (total === 1) {
                frames[0].style.opacity = 1;
                return;
            }

            // We never fade out any image, only fade new ones in on top.
            // Frame 0 is always fully visible.
            // For frames 1..N-1, we fade them in sequentially as progress goes 0->1.

            var eff = progress * (total - 1); // 0 .. total-1
            var idx = Math.floor(eff);        // 0 .. total-1
            var t   = eff - idx;              // 0 .. 1 within this step

            frames.forEach(function (frame, i) {
                var opacity = 0;

                if (i === 0) {
                    // First frame always fully visible
                    opacity = 1;
                } else if (i < idx + 1) {
                    // Any frame whose fade-in is "finished" stays fully visible
                    opacity = 1;
                } else if (i === idx + 1) {
                    // Current frame being introduced fades from 0 -> 1
                    opacity = t;
                } else {
                    // Not yet started fading in
                    opacity = 0;
                }

                frame.style.opacity = opacity;
            });
        }

        function animationLoop(timestamp) {
            // Decide whether we should be animating based on modes
            var smoothingActive = (scrollSmoothing === 'light' || scrollSmoothing === 'strong');
            if (!ensureAllFrames && !smoothingActive) {
                animating = false;
                lastTime = null;
                return;
            }

            if (!animating) {
                lastTime = null;
                return;
            }

            if (lastTime === null) {
                lastTime = timestamp;
            }

            var dt = timestamp - lastTime;
            lastTime = timestamp;

            var diff = targetProgress - animProgress;

            // Choose base speed depending on mode
            var maxProgressPerMs;
            if (ensureAllFrames) {
                // Respect user-defined full-gallery duration
                maxProgressPerMs = 1 / (animationSpeedSeconds * 1000);
            } else {
                // Scroll smoothing speeds
                if (scrollSmoothing === 'strong') {
                    maxProgressPerMs = 1 / 800; // ~0.8s for full range
                } else { // light
                    maxProgressPerMs = 1 / 350; // ~0.35s for full range
                }
            }

            var maxStep = maxProgressPerMs * dt;

            if (Math.abs(diff) <= 0.0001) {
                animProgress = targetProgress;
                render(animProgress);
                animating = false;
                lastTime = null;
                return;
            }

            // Move animProgress toward targetProgress at limited speed
            if (diff > 0) {
                animProgress += Math.min(diff, maxStep);
            } else {
                animProgress += Math.max(diff, -maxStep);
            }

            // Clamp just in case
            if (animProgress < 0) animProgress = 0;
            if (animProgress > 1) animProgress = 1;

            render(animProgress);

            if (animating) {
                window.requestAnimationFrame(animationLoop);
            } else {
                lastTime = null;
            }
        }

        function updateTargetFromScroll() {
            var p = computeScrollProgress();

            var smoothingActive = (scrollSmoothing === 'light' || scrollSmoothing === 'strong');

            if (!ensureAllFrames && !smoothingActive) {
                // Direct mapping: scroll position -> visual progress
                animProgress = p;
                targetProgress = p;
                render(animProgress);
                return;
            }

            // Animated modes: ensureAllFrames or smoothing
            targetProgress = p;

            if (!animating) {
                animating = true;
                window.requestAnimationFrame(animationLoop);
            }
        }

        function onScrollOrResize() {
            updateTargetFromScroll();
        }

        // Initial pass
        syncHeight();
        updateTargetFromScroll();

        window.addEventListener('scroll', onScrollOrResize, { passive: true });
        window.addEventListener('resize', onScrollOrResize);
    };

    $(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/scroll_crossfade_gallery.default',
            initScrollCrossfade
        );
    });

})(jQuery);
