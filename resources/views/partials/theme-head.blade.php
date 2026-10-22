<script>
(function () {
    var saved = null;
    try { saved = localStorage.getItem('theme'); } catch (e) {}
    var dark = saved ? saved === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
    document.documentElement.classList.toggle('dark', dark);
})();
window.toggleTheme = function () {
    var dark = !document.documentElement.classList.contains('dark');
    document.documentElement.classList.toggle('dark', dark);
    try { localStorage.setItem('theme', dark ? 'dark' : 'light'); } catch (e) {}
};
</script>
<style>
    html, body { background: transparent !important; }
    .beach-bg { position: fixed; inset: 0; z-index: -10; pointer-events: none;
        background-size: cover; background-position: center; transition: opacity .7s ease-in-out; }
    .beach-day { background-image: url('{{ asset('images/bg-day-pink.jpg') }}'); opacity: 1; }
    .beach-night { background-image: url('{{ asset('images/bg-night-pink.jpg') }}'); opacity: 0; }
    html.dark .beach-day { opacity: 0; }
    html.dark .beach-night { opacity: 1; }

    /* Living beach (partials/theme-bg): CSS + inline SVG filters on top of the static image.
       .beach-cover is sized exactly like background-size: cover (image is 1680x944), so every
       layer inside it uses image coordinates and stays aligned at any screen size. */
    .beach-anim { position: fixed; inset: 0; z-index: -10; pointer-events: none; overflow: hidden; container-type: size; }
    .beach-cover { position: absolute; left: 50%; top: 50%; width: max(100cqw, 177.966cqh); height: max(100cqh, 56.19cqw); transform: translate(-50%, -50%); }
    .beach-cover > * { position: absolute; }
    .beach-fx { inset: 0; width: 100%; height: 100%; transition: opacity .35s ease-in-out; }
    .beach-anim.is-swapping .beach-fx { opacity: 0; }
    /* Water: sea and shoreline only, fading in below the horizon so the horizon line never wobbles. */
    .beach-water { -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1680 944' preserveAspectRatio='none'%3E%3Cdefs%3E%3ClinearGradient id='g' gradientUnits='userSpaceOnUse' x1='0' y1='492' x2='0' y2='600'%3E%3Cstop offset='0' stop-opacity='0'/%3E%3Cstop offset='1'/%3E%3C/linearGradient%3E%3Cfilter id='b' x='-5%25' y='-5%25' width='110%25' height='110%25'%3E%3CfeGaussianBlur stdDeviation='16'/%3E%3C/filter%3E%3C/defs%3E%3Cpolygon filter='url(%23b)' fill='url(%23g)' points='0,490 1440,490 1430,520 1345,600 1130,670 850,740 570,810 290,866 80,944 0,944'/%3E%3C/svg%3E") 0 0 / 100% 100% no-repeat;
        mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1680 944' preserveAspectRatio='none'%3E%3Cdefs%3E%3ClinearGradient id='g' gradientUnits='userSpaceOnUse' x1='0' y1='492' x2='0' y2='600'%3E%3Cstop offset='0' stop-opacity='0'/%3E%3Cstop offset='1'/%3E%3C/linearGradient%3E%3Cfilter id='b' x='-5%25' y='-5%25' width='110%25' height='110%25'%3E%3CfeGaussianBlur stdDeviation='16'/%3E%3C/filter%3E%3C/defs%3E%3Cpolygon filter='url(%23b)' fill='url(%23g)' points='0,490 1440,490 1430,520 1345,600 1130,670 850,740 570,810 290,866 80,944 0,944'/%3E%3C/svg%3E") 0 0 / 100% 100% no-repeat; }
    /* Palms: right ~30%, top ~70%, with soft edges so there is no seam. */
    .beach-palms { -webkit-mask: linear-gradient(to right, transparent 68%, #000 80%), linear-gradient(to bottom, #000 58%, transparent 72%);
        -webkit-mask-composite: source-in; mask: linear-gradient(to right, transparent 68%, #000 80%), linear-gradient(to bottom, #000 58%, transparent 72%); mask-composite: intersect; }
    .beach-foam { inset: 0; width: 100%; height: 100%; opacity: .15; will-change: transform, opacity; animation: beach-foam 6s ease-in-out infinite alternate; }
    @keyframes beach-foam { to { transform: translate(3px, 4px); opacity: .35; } }
    html.dark .beach-foam { animation-name: beach-foam-night; }
    @keyframes beach-foam-night { to { transform: translate(3px, 4px); opacity: .22; } }
    /* Day clouds: a seamless noise tile drifting sideways (transform only). */
    .beach-clouds { left: 0; top: 0; width: calc(100% + 640px); height: 58%; opacity: .3; transition: opacity .7s ease-in-out;
        background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='640' height='320'%3E%3Cfilter id='c' x='0' y='0' width='100%25' height='100%25'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.0047 0.0094' numOctaves='4' seed='7' stitchTiles='stitch'/%3E%3CfeColorMatrix values='0 0 0 0 1 0 0 0 0 0.84 0 0 0 0 0.92 2.2 0 0 0 -0.95'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23c)'/%3E%3C/svg%3E") 0 0 / 640px 320px repeat;
        -webkit-mask-image: linear-gradient(to bottom, #000 40%, transparent 100%); mask-image: linear-gradient(to bottom, #000 40%, transparent 100%);
        will-change: transform; animation: beach-clouds 80s linear infinite; }
    @keyframes beach-clouds { to { transform: translateX(-640px); } }
    /* Night stars: a few tiny twinkles (opacity only). */
    .beach-stars { inset: 0; opacity: 0; transition: opacity .7s ease-in-out; }
    .beach-stars i { position: absolute; width: 3px; height: 3px; margin: -1.5px; border-radius: 9999px; background: #fff;
        box-shadow: 0 0 6px 1px rgba(255, 220, 245, .8); opacity: .25; will-change: opacity; animation: beach-twinkle 3.6s ease-in-out infinite alternate; }
    @keyframes beach-twinkle { to { opacity: 1; } }
    /* Night moon (image centre 68.9%, 23.7%): a soft pink glow that breathes (transform/opacity only). */
    .beach-moon { left: 68.9%; top: 23.7%; width: 13%; aspect-ratio: 1; margin: -6.5% 0 0 -6.5%; border-radius: 9999px; opacity: 0; transition: opacity .7s ease-in-out;
        background: radial-gradient(circle, rgba(255, 214, 236, .55) 0%, rgba(249, 168, 212, .28) 22%, rgba(236, 72, 153, .10) 45%, transparent 70%); }
    .beach-moon::after { content: ''; position: absolute; inset: 0; border-radius: inherit; background: inherit;
        will-change: transform, opacity; animation: beach-moon 5s ease-in-out infinite alternate; }
    @keyframes beach-moon { from { transform: scale(.92); opacity: .35; } to { transform: scale(1.08); opacity: 1; } }
    html.dark .beach-clouds { opacity: 0; }
    html.dark .beach-stars, html.dark .beach-moon { opacity: 1; }
    html:not(.dark) .beach-stars i, html:not(.dark) .beach-moon::after, html.dark .beach-clouds, .beach-anim.is-paused *, .beach-anim.is-paused .beach-moon::after { animation-play-state: paused; }
    @media (prefers-reduced-motion: reduce) { .beach-anim { display: none; } }
    .beach-tint { position: fixed; inset: 0; z-index: -9; pointer-events: none;
        background: rgba(255,255,255,.20); transition: background-color .7s ease-in-out; }
    html.dark .beach-tint { background: rgba(10,20,45,.40); }
    @media (prefers-reduced-motion: reduce) { .beach-bg, .beach-tint { transition: none; } }
    .theme-toggle { position: relative; z-index: 50; cursor: pointer; }
    .theme-toggle .icon-sun { display: none; }
    html.dark .theme-toggle .icon-sun { display: inline; }
    html.dark .theme-toggle .icon-moon { display: none; }
</style>
