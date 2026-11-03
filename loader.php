<!-- ═══════════════════════════════════════════════════════════
     MAJOY'S LOADING SCREEN  –  include at top of <body>
     Palette: #7B5EA7 purple family (matches admin/customer/staff)
     ═══════════════════════════════════════════════════════════ -->
<div id="majoys-loader" class="mloader-overlay">
    <div class="mloader-bg"></div>

    <!-- Floating particles -->
    <div class="mloader-particles">
        <span class="mparticle" style="--x:12%;--y:20%;--d:0s;--s:1.2">🍰</span>
        <span class="mparticle" style="--x:82%;--y:15%;--d:.4s;--s:.8">🧁</span>
        <span class="mparticle" style="--x:72%;--y:70%;--d:.8s;--s:1.1">🍓</span>
        <span class="mparticle" style="--x:18%;--y:75%;--d:1.2s;--s:.9">🍮</span>
        <span class="mparticle" style="--x:50%;--y:10%;--d:.2s;--s:.7">✨</span>
        <span class="mparticle" style="--x:90%;--y:50%;--d:.6s;--s:1.0">🌸</span>
        <span class="mparticle" style="--x:5%;--y:50%;--d:1.0s;--s:.85">🍬</span>
        <span class="mparticle" style="--x:40%;--y:85%;--d:1.4s;--s:1.3">🎂</span>
        <span class="mparticle" style="--x:65%;--y:88%;--d:.3s;--s:.75">⭐</span>
        <span class="mparticle" style="--x:28%;--y:8%;--d:.9s;--s:1.0">🌷</span>
    </div>

    <!-- Central card -->
    <div class="mloader-card">
        <!-- Ring + Logo stacked together -->
        <div class="mloader-logo-stack">
            <div class="mloader-ring">
                <svg viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="60" cy="60" r="54" stroke="url(#purpleGrad)" stroke-width="2.5"
                        stroke-dasharray="340" stroke-dashoffset="340"
                        class="mring-circle" stroke-linecap="round"/>
                    <defs>
                        <linearGradient id="purpleGrad" x1="0" y1="0" x2="120" y2="120">
                            <stop offset="0%"   stop-color="#C4A8E0"/>
                            <stop offset="50%"  stop-color="#7B5EA7"/>
                            <stop offset="100%" stop-color="#5A3F84"/>
                        </linearGradient>
                    </defs>
                </svg>
            </div>
            <div class="mloader-logo-wrap">
                <img src="logo.png" alt="Majoy's" class="mloader-logo">
            </div>
        </div>

        <!-- Brand name -->
        <h1 class="mloader-brand">Majoy's</h1>
        <p class="mloader-sub">Homemade Sweets</p>

        <!-- Dots -->
        <div class="mloader-dots">
            <span></span><span></span><span></span>
        </div>

        <p class="mloader-msg">Preparing your sweets<span class="mloader-ellipsis"></span></p>
    </div>
</div>

<style>
/* ══ LOADER OVERLAY ══ */
.mloader-overlay {
    position: fixed;
    inset: 0;
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    transition: opacity .6s ease, visibility .6s ease;
}

.mloader-overlay.hide {
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
}

/* ── Background — deep purple gradient matching admin dark theme ── */
.mloader-bg {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 70% 55% at 15% 10%,  rgba(123,94,167,.38) 0%, transparent 55%),
        radial-gradient(ellipse 55% 70% at 85% 90%,  rgba(90,63,132,.32)  0%, transparent 55%),
        radial-gradient(ellipse 40% 40% at 50% 50%,  rgba(61,36,104,.20)  0%, transparent 65%),
        linear-gradient(160deg, #1A1426 0%, #3D2468 48%, #241C38 100%);
    animation: loaderBgPulse 4s ease-in-out infinite alternate;
}

@keyframes loaderBgPulse {
    from { filter: brightness(1);    }
    to   { filter: brightness(1.10); }
}

/* ── Floating particles ── */
.mloader-particles {
    position: absolute;
    inset: 0;
    pointer-events: none;
}

.mparticle {
    position: absolute;
    left: var(--x);
    top:  var(--y);
    font-size: calc(var(--s) * 22px);
    opacity: 0;
    animation: particleFloat 5s ease-in-out infinite;
    animation-delay: var(--d);
    filter: drop-shadow(0 4px 12px rgba(155,127,191,.45));
}

@keyframes particleFloat {
    0%   { opacity: 0;   transform: translateY(0px)   rotate(0deg);  }
    15%  { opacity: .75; }
    50%  { opacity: .55; transform: translateY(-22px) rotate(8deg);  }
    85%  { opacity: .75; }
    100% { opacity: 0;   transform: translateY(4px)   rotate(-4deg); }
}

/* ── Central card ── */
.mloader-card {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0;
    text-align: center;
    padding: 0 24px;
    animation: cardReveal .8s cubic-bezier(.34,1.56,.64,1) forwards;
    opacity: 0;
}

@keyframes cardReveal {
    from { opacity: 0; transform: scale(.82) translateY(16px); }
    to   { opacity: 1; transform: scale(1)   translateY(0);    }
}

/* ── Ring + Logo Stack ── */
.mloader-logo-stack {
    position: relative;
    width: 110px;
    height: 110px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
}

/* Spinning ring — fills the stack container */
.mloader-ring {
    position: absolute;
    inset: 0;
    animation: ringRotate 2.8s linear infinite;
}

@keyframes ringRotate {
    to { transform: rotate(360deg); }
}

.mring-circle {
    animation: ringDraw 1.2s cubic-bezier(.4,0,.2,1) .3s forwards;
}

@keyframes ringDraw {
    to { stroke-dashoffset: 0; }
}

/* Logo — centered inside the ring */
.mloader-logo-wrap {
    width: 82px;
    height: 82px;
    border-radius: 50%;
    overflow: hidden;
    background: rgba(255,255,255,.06);
    backdrop-filter: blur(12px);
    border: 2px solid rgba(123,94,167,.40);
    box-shadow:
        0 0 0 6px rgba(123,94,167,.10),
        0 8px 32px rgba(0,0,0,.45);
    animation: logoPulse 2.5s ease-in-out infinite;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    z-index: 1;
}

@keyframes logoPulse {
    0%, 100% { box-shadow: 0 0 0 6px  rgba(123,94,167,.10), 0 8px  32px rgba(0,0,0,.45); }
    50%       { box-shadow: 0 0 0 14px rgba(123,94,167,.22), 0 12px 40px rgba(0,0,0,.55); }
}

.mloader-logo {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* ── Brand text ── */
.mloader-brand {
    margin-top: 18px;
    font-family: 'Cormorant Garamond', 'Playfair Display', Georgia, serif;
    font-size: 38px;
    font-weight: 700;
    font-style: italic;
    letter-spacing: .02em;
    background: linear-gradient(135deg, #C4A8E0 0%, #7B5EA7 50%, #B89FD4 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: textShimmer 2.4s linear infinite;
    background-size: 200% auto;
}

@keyframes textShimmer {
    from { background-position: 0%   center; }
    to   { background-position: 200% center; }
}

.mloader-sub {
    margin-top: 2px;
    font-family: 'Plus Jakarta Sans', 'DM Sans', sans-serif;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: .22em;
    text-transform: uppercase;
    color: rgba(196,168,224,.55);
}

/* ── Animated dots ── */
.mloader-dots {
    display: flex;
    gap: 7px;
    margin-top: 28px;
}

.mloader-dots span {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: linear-gradient(135deg, #7B5EA7, #9B7FBF);
    animation: dotBounce 1.2s ease-in-out infinite;
}

.mloader-dots span:nth-child(1) { animation-delay: 0s;   }
.mloader-dots span:nth-child(2) { animation-delay: .18s; }
.mloader-dots span:nth-child(3) { animation-delay: .36s; }

@keyframes dotBounce {
    0%, 80%, 100% { transform: scale(1);   opacity: .6; }
    40%            { transform: scale(1.6); opacity: 1;  }
}

/* ── Message ── */
.mloader-msg {
    margin-top: 14px;
    font-family: 'Plus Jakarta Sans', 'DM Sans', sans-serif;
    font-size: 13px;
    color: rgba(196,168,224,.45);
    letter-spacing: .06em;
}

.mloader-ellipsis::after {
    content: '';
    animation: ellipsis 1.8s steps(4, end) infinite;
}

@keyframes ellipsis {
    0%   { content: '';    }
    25%  { content: '.';   }
    50%  { content: '..';  }
    75%  { content: '...'; }
    100% { content: '';    }
}
</style>

<script>
(function() {
    function hideLoader() {
        const loader = document.getElementById('majoys-loader');
        if (loader) {
            const shown = Date.now() - window._loaderStart;
            const wait  = Math.max(0, 1600 - shown);
            setTimeout(function() {
                loader.classList.add('hide');
                setTimeout(function() { loader.remove(); }, 650);
            }, wait);
        }
    }
    window._loaderStart = Date.now();
    if (document.readyState === 'complete') {
        hideLoader();
    } else {
        window.addEventListener('load', hideLoader);
        setTimeout(hideLoader, 4000);
    }
})();
</script>