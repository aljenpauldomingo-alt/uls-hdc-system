<div class="beach-bg beach-day" aria-hidden="true"></div>
<div class="beach-bg beach-night" aria-hidden="true"></div>
{{-- Living beach: rippling water, swaying palms, shoreline foam, drifting clouds (day) and twinkling
     stars (night). Coordinates are image pixels (1680x944). Hidden with prefers-reduced-motion and
     paused while the tab is hidden; styles are in partials/theme-head. --}}
<div class="beach-anim" aria-hidden="true" data-beach-anim
    data-day="{{ asset('images/bg-day-pink.jpg') }}" data-night="{{ asset('images/bg-night-pink.jpg') }}">
    <div class="beach-cover">
        <div class="beach-clouds"></div>
        <div class="beach-stars">
            @foreach ([[6, 8, 0], [14, 22, 1.2], [22, 5, 2.1], [31, 15, .6], [38, 30, 2.8], [45, 9, 1.7], [52, 20, .3], [58, 4, 2.4], [63, 34, 1], [74, 10, 3.1], [77, 38, 1.5], [10, 36, 2.6], [27, 40, .9], [48, 38, 3.4]] as [$x, $y, $delay])
                <i style="left: {{ $x }}%; top: {{ $y }}%; animation-delay: -{{ $delay }}s"></i>
            @endforeach
        </div>

        <svg class="beach-fx beach-water" viewBox="0 0 1680 944" preserveAspectRatio="none" focusable="false">
            <filter id="beach-water-fx" filterUnits="userSpaceOnUse" x="0" y="470" width="1460" height="474" color-interpolation-filters="sRGB">
                <feTurbulence data-beach-noise="water" type="fractalNoise" baseFrequency="0.008 0.06" numOctaves="2" seed="4" result="noise" />
                <feDisplacementMap in="SourceGraphic" in2="noise" scale="8" xChannelSelector="R" yChannelSelector="G" />
            </filter>
            <image data-beach-image href="{{ asset('images/bg-day-pink.jpg') }}" width="1680" height="944" preserveAspectRatio="none" filter="url(#beach-water-fx)" />
        </svg>

        <svg class="beach-fx beach-palms" viewBox="0 0 1680 944" preserveAspectRatio="none" focusable="false">
            <filter id="beach-palm-fx" filterUnits="userSpaceOnUse" x="1092" y="0" width="588" height="720" color-interpolation-filters="sRGB">
                <feTurbulence data-beach-noise="palms" type="fractalNoise" baseFrequency="0.012 0.02" numOctaves="2" seed="9" result="noise" />
                <feDisplacementMap in="SourceGraphic" in2="noise" scale="3.5" xChannelSelector="R" yChannelSelector="G" />
            </filter>
            <image data-beach-image href="{{ asset('images/bg-day-pink.jpg') }}" width="1680" height="944" preserveAspectRatio="none" filter="url(#beach-palm-fx)" />
        </svg>

        <svg class="beach-foam" viewBox="0 0 1680 944" preserveAspectRatio="none" focusable="false">
            <filter id="beach-foam-blur" x="-5%" y="-20%" width="110%" height="140%"><feGaussianBlur stdDeviation="5" /></filter>
            <polyline points="0,900 280,842 560,786 840,716 1120,646 1330,576 1414,520" fill="none" stroke="#fff" stroke-width="14"
                stroke-linecap="round" stroke-linejoin="round" filter="url(#beach-foam-blur)" />
        </svg>
    </div>
</div>
<div class="beach-tint" aria-hidden="true"></div>
<script>
(function () {
    var root = document.querySelector('[data-beach-anim]');
    if (!root) { return; }
    var html = document.documentElement;
    var images = root.querySelectorAll('[data-beach-image]');
    var water = root.querySelector('[data-beach-noise="water"]');
    var palms = root.querySelector('[data-beach-noise="palms"]');
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
    var theme = null, raf = null, last = 0, swap = null;

    // The filtered copies must show the same picture as the base layer.
    function setImages() {
        var dark = html.classList.contains('dark');
        if (dark === theme) { return; }
        var first = theme === null;
        theme = dark;
        var apply = function () {
            for (var i = 0; i < images.length; i++) { images[i].setAttribute('href', dark ? root.dataset.night : root.dataset.day); }
            root.classList.remove('is-swapping');
        };
        clearTimeout(swap);
        if (first) { apply(); return; }
        root.classList.add('is-swapping');
        swap = setTimeout(apply, 350);
    }

    // ~30 fps is plenty for slow water; each step only changes two filter attributes (no layout).
    function frame(now) {
        raf = requestAnimationFrame(frame);
        if (now - last < 33) { return; }
        last = now;
        var t = now / 1000, tau = Math.PI * 2;
        water.setAttribute('baseFrequency', (0.008 + 0.0006 * Math.sin(t * tau / 12)).toFixed(5) + ' ' + (0.06 + 0.004 * Math.sin(t * tau / 12 + 1.3)).toFixed(5));
        palms.setAttribute('baseFrequency', (0.012 + 0.0015 * Math.sin(t * tau / 8)).toFixed(5) + ' ' + (0.02 + 0.002 * Math.sin(t * tau / 8 + 2)).toFixed(5));
    }

    function update() {
        var run = !reduced.matches && !document.hidden;
        root.classList.toggle('is-paused', !run);
        if (run && raf === null) { raf = requestAnimationFrame(frame); }
        if (!run && raf !== null) { cancelAnimationFrame(raf); raf = null; }
    }

    setImages();
    new MutationObserver(setImages).observe(html, { attributes: true, attributeFilter: ['class'] });
    document.addEventListener('visibilitychange', update);
    if (reduced.addEventListener) { reduced.addEventListener('change', update); }
    update();
})();
</script>
