<!DOCTYPE html>
<html lang="en">
<script>
(function(){
  var s = localStorage.getItem('mj_theme') || 'light';
  if (!['light','dark'].includes(s)) s = 'light';
  document.documentElement.setAttribute('data-theme', s);
})();
</script>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Majoy's Homemade Sweets — Create Account</title>
    <link rel="stylesheet" href="majoys.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>

/* ══════════════════════════════════════════════
   2-WAY THEME — Admin-matched #7B5EA7 palette
   ══════════════════════════════════════════════ */

:root, [data-theme="light"] {
    --bg:            #EDE7F6;
    --surface:       #FFFFFF;
    --surface2:      #F3EEF9;
    --brand:         #7B5EA7;
    --brand-mid:     #9B7FBF;
    --brand-light:   #C4A8E0;
    --brand-pale:    #EDE7F6;
    --brand-dark:    #5A3F84;
    --brand-deeper:  #3D2468;
    --text:          #1A1A1A;
    --text-mid:      #4A3B6B;
    --text-muted:    #757575;
    --border:        rgba(123,94,167,0.18);
    --border-soft:   rgba(123,94,167,0.10);
    --shadow-lg:     0 8px 40px rgba(123,94,167,0.18);
    /* card */
    --card-bg:       rgba(255,255,255,0.97);
    --input-bg:      #F3EEF9;
    --input-border:  rgba(123,94,167,0.22);
    --input-focus:   #7B5EA7;
    --input-text:    #1A1A1A;
    --btn-bg:        linear-gradient(135deg,#7B5EA7 0%,#9B7FBF 100%);
    --btn-hover:     linear-gradient(135deg,#5A3F84,#7B5EA7);
    --btn-shadow:    0 4px 18px rgba(123,94,167,0.32);
    --link-color:    #5A3F84;
    --divider:       rgba(123,94,167,0.15);
    --footer-text:   #9B7FBF;
    --accent-top:    linear-gradient(90deg,transparent,#7B5EA7,#C4A8E0,#7B5EA7,transparent);
    --notice-bg:     #F3EEF9;
    --notice-border: rgba(123,94,167,0.20);
    --notice-title:  #7B5EA7;
}

[data-theme="dark"] {
    --bg:            #1A1426;
    --surface:       #241C38;
    --surface2:      #2E2448;
    --brand:         #9B7FBF;
    --brand-mid:     #B89FD4;
    --brand-light:   #7B5EA7;
    --brand-pale:    #2E2448;
    --brand-dark:    #C4A8E0;
    --brand-deeper:  #EDE7F6;
    --text:          #F0EAF8;
    --text-mid:      #C4A8E0;
    --text-muted:    #8E7BAA;
    --border:        rgba(155,127,191,0.20);
    --border-soft:   rgba(155,127,191,0.10);
    --shadow-lg:     0 8px 40px rgba(0,0,0,0.55);
    /* card */
    --card-bg:       rgba(36,28,56,0.98);
    --input-bg:      #1A1426;
    --input-border:  rgba(155,127,191,0.25);
    --input-focus:   #9B7FBF;
    --input-text:    #F0EAF8;
    --btn-bg:        linear-gradient(135deg,#7B5EA7 0%,#9B7FBF 100%);
    --btn-hover:     linear-gradient(135deg,#5A3F84,#7B5EA7);
    --btn-shadow:    0 4px 18px rgba(123,94,167,0.40);
    --link-color:    #B89FD4;
    --divider:       rgba(155,127,191,0.15);
    --footer-text:   #8E7BAA;
    --accent-top:    linear-gradient(90deg,transparent,#9B7FBF,#C4A8E0,#9B7FBF,transparent);
    --notice-bg:     #2E2448;
    --notice-border: rgba(155,127,191,0.20);
    --notice-title:  #B89FD4;
}

*,*::before,*::after { margin:0; padding:0; box-sizing:border-box; }
html, body { height:100%; font-family:'Plus Jakarta Sans','DM Sans',sans-serif; }

body {
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    position:relative;
    overflow:hidden;
    background: var(--bg);
    transition: background 0.3s;
}

body::before {
    content:'';
    position:absolute;
    inset:0;
    background:
        radial-gradient(ellipse 70% 55% at 15% 10%, rgba(123,94,167,0.28) 0%, transparent 60%),
        radial-gradient(ellipse 55% 70% at 85% 90%, rgba(90,63,132,0.22) 0%, transparent 60%),
        radial-gradient(ellipse 40% 40% at 50% 50%, rgba(196,168,224,0.10) 0%, transparent 70%);
    z-index:0;
}

[data-theme="dark"] body::before {
    background:
        radial-gradient(ellipse 70% 55% at 15% 10%, rgba(61,36,104,0.45) 0%, transparent 60%),
        radial-gradient(ellipse 55% 70% at 85% 90%, rgba(42,28,72,0.50) 0%, transparent 60%),
        radial-gradient(ellipse 40% 40% at 50% 50%, rgba(26,20,38,0.30) 0%, transparent 70%);
}

/* ── CARD ── */
.login-container {
    position:relative;
    z-index:1;
    background: var(--card-bg);
    backdrop-filter: blur(20px);
    padding: 44px 48px;
    border-radius: 28px;
    box-shadow:
        var(--shadow-lg),
        0 0 0 1px var(--border),
        0 32px 80px rgba(123,94,167,0.18);
    width:100%;
    max-width:430px;
    text-align:center;
    max-height:95vh;
    overflow-y:auto;
    scrollbar-width:none;
    animation: cardIn .6s cubic-bezier(.34,1.56,.64,1);
    border: 1px solid var(--border);
}
.login-container::-webkit-scrollbar { display:none; }

.login-container::before {
    content:'';
    position:absolute;
    top:0; left:8%; right:8%;
    height:3px;
    background: var(--accent-top);
    border-radius: 0 0 3px 3px;
}

/* ── LOGO ── */
.logo-container { margin-bottom:14px; }
.logo {
    width:82px;
    height:auto;
    filter: drop-shadow(0 4px 18px rgba(123,94,167,0.35));
    animation: logoReveal .7s cubic-bezier(.34,1.56,.64,1) .2s both;
}

/* ── BRAND ── */
.brand-name {
    font-family:'Cormorant Garamond',serif;
    font-size:26px;
    font-style:italic;
    font-weight:700;
    color: var(--brand);
    margin-bottom:3px;
    letter-spacing:.01em;
}
.tagline {
    color: var(--brand-mid);
    font-size:10.5px;
    font-weight:700;
    letter-spacing:.18em;
    text-transform:uppercase;
    margin-bottom:26px;
}
.welcome-text {
    font-family:'Cormorant Garamond',serif;
    font-size:23px;
    color: var(--text);
    margin-bottom:10px;
    font-weight:600;
}
.register-subtitle {
    color: var(--text-muted);
    font-size:13px;
    margin-bottom:20px;
}

/* ── FORM ── */
.login-form { display:flex; flex-direction:column; gap:13px; text-align:left; }
.input-group { position:relative; }
.input-group input {
    width:100%;
    padding:13px 18px;
    border: 1.5px solid var(--input-border);
    background: var(--input-bg);
    border-radius:12px;
    font-size:14px;
    font-family:'Plus Jakarta Sans',sans-serif;
    color: var(--input-text);
    outline:none;
    transition: all .25s ease;
}
.input-group input::placeholder { color: var(--text-muted); }
.input-group input:focus {
    border-color: var(--input-focus);
    background: var(--surface);
    box-shadow: 0 0 0 4px rgba(123,94,167,0.12);
}

.show-password-container { display:flex; align-items:center; gap:8px; margin-top:-4px; }
.show-password-container input[type="checkbox"] { width:15px; height:15px; accent-color: var(--brand); cursor:pointer; }
.show-password-container label { color: var(--text-muted); font-size:12.5px; cursor:pointer; user-select:none; font-weight:500; }

.login-btn {
    width:100%;
    padding:14px;
    background: var(--btn-bg);
    color:white;
    border:none;
    border-radius:12px;
    font-size:14.5px;
    font-weight:700;
    font-family:'Plus Jakarta Sans',sans-serif;
    cursor:pointer;
    transition: all .3s ease;
    letter-spacing:.05em;
    box-shadow: var(--btn-shadow);
    margin-top:4px;
}
.login-btn:hover {
    transform:translateY(-2px);
    box-shadow: 0 8px 26px rgba(123,94,167,0.42);
    background: var(--btn-hover);
}
.login-btn:active { transform:translateY(0); }

/* ── MESSAGES ── */
.error-msg {
    background: rgba(244,67,54,0.10);
    color: #C62828;
    padding:10px 14px;
    border-radius:9px;
    border-left:3px solid #F44336;
    font-size:13px;
    font-weight:600;
    margin-bottom:6px;
}
.success-msg {
    background: rgba(76,175,80,0.10);
    color: #2E7D32;
    padding:10px 14px;
    border-radius:9px;
    border-left:3px solid #4CAF50;
    font-size:13px;
    font-weight:600;
    margin-bottom:6px;
}

/* ── SIGNUP TEXT ── */
.signup-text { margin-top:18px; color: var(--text-muted); font-size:13.5px; }
.signup-link { color: var(--brand-dark); text-decoration:none; font-weight:700; transition:color .2s; }
.signup-link:hover { color: var(--brand); text-decoration:underline; }

/* ── NOTICE BOX ── */
.notice-box {
    margin-top:18px;
    padding:14px 16px;
    background: var(--notice-bg);
    border: 1px solid var(--notice-border);
    border-radius:12px;
    font-size:12px;
    color: var(--text-muted);
    text-align:left;
    line-height:1.6;
}
.notice-box strong { color: var(--notice-title); }

/* ── PASSWORD STRENGTH ── */
.password-strength { height:3px; border-radius:2px; background: var(--border); margin-top:6px; overflow:hidden; }
.strength-bar { height:100%; transition:all .3s; border-radius:2px; width:0; }
.strength-strong { background:#4CAF50; width:100%; }
.strength-text { font-size:11px; margin-top:4px; font-weight:600; }

@keyframes cardIn {
    from { opacity:0; transform:translateY(24px) scale(0.97); }
    to   { opacity:1; transform:translateY(0) scale(1); }
}
@keyframes logoReveal {
    from { opacity:0; transform:scale(0.8) translateY(10px); }
    to   { opacity:1; transform:scale(1) translateY(0); }
}

@media(max-width:600px) {
    .login-container { padding:30px 22px; margin:14px; border-radius:20px; }
    .brand-name { font-size:22px; }
    .welcome-text { font-size:19px; }
}

/* ── THEME TOGGLE (floating) ── */
.theme-fab {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 200;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--brand);
    border: 2px solid rgba(255,255,255,0.25);
    color: white;
    font-size: 17px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 16px rgba(123,94,167,0.35);
    transition: all 0.2s;
    line-height: 1;
}
.theme-fab:hover { transform: rotate(20deg) scale(1.1); box-shadow: 0 6px 20px rgba(123,94,167,0.5); }

    </style>
</head>
<body>

<!-- Theme toggle FAB -->
<button class="theme-fab" id="mjThemeBtn" onclick="mjCycleTheme()" title="Switch theme">☀️</button>

<!-- MAJOY LOADER -->
<div id="mjLoader" style="position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;overflow:hidden;transition:opacity .6s,visibility .6s;">
  <div style="position:absolute;inset:0;background:radial-gradient(ellipse 70% 55% at 15% 10%,rgba(123,94,167,0.45) 0%,transparent 60%),radial-gradient(ellipse 55% 70% at 85% 90%,rgba(90,63,132,0.40) 0%,transparent 60%),linear-gradient(160deg,#1A1426 0%,#3D2468 50%,#241C38 100%);animation:mjLbg 4s ease-in-out infinite alternate;"></div>
  <div style="position:absolute;inset:0;pointer-events:none;">
    <span style="position:absolute;left:12%;top:20%;font-size:26px;opacity:0;animation:mjP 5s ease-in-out infinite 0s">🍰</span>
    <span style="position:absolute;left:82%;top:15%;font-size:18px;opacity:0;animation:mjP 5s ease-in-out infinite .4s">🧁</span>
    <span style="position:absolute;left:72%;top:70%;font-size:24px;opacity:0;animation:mjP 5s ease-in-out infinite .8s">🍓</span>
    <span style="position:absolute;left:18%;top:75%;font-size:20px;opacity:0;animation:mjP 5s ease-in-out infinite 1.2s">🍮</span>
    <span style="position:absolute;left:50%;top:10%;font-size:16px;opacity:0;animation:mjP 5s ease-in-out infinite .2s">✨</span>
    <span style="position:absolute;left:90%;top:50%;font-size:22px;opacity:0;animation:mjP 5s ease-in-out infinite .6s">🌸</span>
    <span style="position:absolute;left:5%;top:50%;font-size:19px;opacity:0;animation:mjP 5s ease-in-out infinite 1s">🍬</span>
    <span style="position:absolute;left:40%;top:85%;font-size:28px;opacity:0;animation:mjP 5s ease-in-out infinite 1.4s">🎂</span>
    <span style="position:absolute;left:65%;top:88%;font-size:17px;opacity:0;animation:mjP 5s ease-in-out infinite .3s">⭐</span>
    <span style="position:absolute;left:28%;top:8%;font-size:21px;opacity:0;animation:mjP 5s ease-in-out infinite .9s">🌷</span>
  </div>
  <div id="mjCard" style="position:relative;z-index:1;display:flex;flex-direction:column;align-items:center;text-align:center;padding:0 24px;animation:mjCardIn .8s cubic-bezier(.34,1.56,.64,1) forwards;opacity:0;">
    <div style="position:relative;width:110px;height:110px;display:flex;align-items:center;justify-content:center;margin-bottom:16px;"><div style="position:absolute;inset:0;animation:mjRing 2.8s linear infinite;">
      <svg viewBox="0 0 120 120" fill="none" style="width:100%;height:100%;">
        <circle cx="60" cy="60" r="54" stroke="url(#mjGrad2)" stroke-width="2.5" stroke-dasharray="340" stroke-dashoffset="340" class="mjCirc" stroke-linecap="round"/>
        <defs><linearGradient id="mjGrad2" x1="0" y1="0" x2="120" y2="120"><stop offset="0%" stop-color="#C4A8E0"/><stop offset="50%" stop-color="#7B5EA7"/><stop offset="100%" stop-color="#5A3F84"/></linearGradient></defs>
      </svg>
    </div>
    <div style="width:84px;height:84px;border-radius:50%;overflow:hidden;position:relative;z-index:1;border:2px solid rgba(123,94,167,0.35);box-shadow:0 0 0 6px rgba(123,94,167,0.10),0 8px 32px rgba(0,0,0,.4);animation:mjLogoPulse 2.5s ease-in-out infinite;"><img src="logo.png" alt="Majoy's" style="width:100%;height:100%;object-fit:cover;"></div></div>
    <h1 style="margin-top:18px;font-family:'Cormorant Garamond',Georgia,serif;font-size:38px;font-weight:700;font-style:italic;background:linear-gradient(135deg,#C4A8E0,#7B5EA7,#9B7FBF);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;background-size:200% auto;animation:mjShimmer 2s linear infinite;">Majoy's</h1>
    <p style="margin-top:3px;font-size:11px;font-weight:700;letter-spacing:.2em;text-transform:uppercase;color:rgba(255,255,255,.4);">Homemade Sweets</p>
    <div style="display:flex;gap:7px;margin-top:26px;">
      <span style="width:7px;height:7px;border-radius:50%;background:linear-gradient(135deg,#7B5EA7,#9B7FBF);display:inline-block;animation:mjDot 1.2s ease-in-out infinite 0s;"></span>
      <span style="width:7px;height:7px;border-radius:50%;background:linear-gradient(135deg,#7B5EA7,#9B7FBF);display:inline-block;animation:mjDot 1.2s ease-in-out infinite .18s;"></span>
      <span style="width:7px;height:7px;border-radius:50%;background:linear-gradient(135deg,#7B5EA7,#9B7FBF);display:inline-block;animation:mjDot 1.2s ease-in-out infinite .36s;"></span>
    </div>
    <p style="margin-top:12px;font-size:12px;color:rgba(255,255,255,.35);letter-spacing:.05em;">Freshly baked with love<span id="mjEll"></span></p>
  </div>
</div>
<style>
@keyframes mjLbg{from{filter:brightness(1)}to{filter:brightness(1.08)}}
@keyframes mjP{0%,100%{opacity:0;transform:translateY(10px) scale(.8)}30%,70%{opacity:.8;transform:translateY(0) scale(1)}}
@keyframes mjCardIn{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:none}}
@keyframes mjRing{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}
@keyframes mjLogoPulse{0%,100%{box-shadow:0 0 0 6px rgba(123,94,167,.10),0 8px 32px rgba(0,0,0,.4)}50%{box-shadow:0 0 0 10px rgba(123,94,167,.18),0 8px 32px rgba(0,0,0,.4)}}
@keyframes mjShimmer{from{background-position:200% center}to{background-position:-200% center}}
@keyframes mjDot{0%,80%,100%{transform:scale(0);opacity:0}40%{transform:scale(1);opacity:1}}
.mjCirc{animation:mjDraw 1.8s cubic-bezier(.4,0,.2,1) forwards .3s}
@keyframes mjDraw{from{stroke-dashoffset:340}to{stroke-dashoffset:0}}
</style>
<script>
(function(){
  var el=document.getElementById("mjEll");
  if(el){var d=["",".","..","."],i=0;setInterval(function(){el.textContent=d[i=(i+1)%4]},450);}
  function hide(){
    var l=document.getElementById("mjLoader");
    if(!l)return;
    var waited=Date.now()-window._mjStart;
    setTimeout(function(){l.style.opacity="0";l.style.visibility="hidden";setTimeout(function(){if(l.parentNode)l.parentNode.removeChild(l);},650);},Math.max(0,1600-waited));
  }
  window._mjStart=Date.now();
  if(document.readyState==="complete")hide();
  else{window.addEventListener("load",hide);setTimeout(hide,4000);}
})();
</script>

    <div class="login-container">
        <div class="logo-container">
            <img src="logo.png" alt="Majoy's Logo" class="logo">
        </div>

        <h1 class="brand-name">Majoy's Homemade Sweets</h1>
        <p class="tagline">Freshly baked with love</p>

        <h2 class="welcome-text">Create Account</h2>
        <p class="register-subtitle">Join us and start ordering delicious homemade sweets!</p>

        <form method="POST" action="register_process.php" class="login-form">
            <div class="input-group">
                <input type="text" name="first_name" placeholder="First Name" required autocomplete="given-name" maxlength="50" style="margin-bottom:10px;">
                <input type="text" name="last_name" placeholder="Last Name" required autocomplete="family-name" maxlength="50">
            </div>

            <div class="input-group">
                <input type="text" name="username" placeholder="Username" required autocomplete="username">
            </div>

            <div class="input-group">
                <input type="password" name="password" id="password" placeholder="Password" required autocomplete="new-password">
            </div>

            <div class="input-group">
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required autocomplete="new-password">
            </div>

            <div class="input-group">
                <input type="tel" name="phone" id="phoneInput"
                    placeholder="Phone Number (e.g. 09171234567)"
                    autocomplete="tel"
                    maxlength="11"
                    pattern="[0-9]{11}"
                    inputmode="numeric"
                    oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,11);"
                    required>
                <small id="phoneHint" style="color:var(--text-muted);font-size:11px;margin-top:5px;display:block;">📱 Must be exactly 11 digits (e.g. 09171234567)</small>
            </div>

            <div class="input-group">
                <textarea name="address" placeholder="Complete Address&#10;e.g. Blk 3 Lot 5 Sampaguita St., Brgy. Kauswagan, Cagayan de Oro City, Misamis Oriental"
                    rows="3"
                    style="width:100%;padding:13px 18px;border:1.5px solid var(--input-border);background:var(--input-bg);border-radius:12px;font-size:13px;font-family:'Plus Jakarta Sans',sans-serif;color:var(--input-text);outline:none;transition:all .25s;resize:vertical;line-height:1.5;"
                    onfocus="this.style.borderColor='var(--input-focus)';this.style.background='var(--surface)';this.style.boxShadow='0 0 0 4px rgba(123,94,167,0.12)';"
                    onblur="this.style.borderColor='';this.style.background='';this.style.boxShadow='';"></textarea>
                <small style="color:var(--text-muted);font-size:11px;margin-top:5px;display:block;line-height:1.5;">
                    📍 Include: House/Unit No., Street, Barangay, City/Municipality, Province
                </small>
            </div>

            <div class="show-password-container">
                <input type="checkbox" id="showPassword" onclick="togglePassword()">
                <label for="showPassword">Show Password</label>
            </div>

            <button type="submit" class="login-btn">Create Account</button>
        </form>

        <p class="signup-text">
            Already have an account?&nbsp;<a href="login.php" class="signup-link">Sign in</a>
        </p>

        <div class="notice-box">
            <strong>📝 Customer Registration Only</strong><br>
            Staff, Admin, and Owner accounts are managed through the internal system.
        </div>
    </div>

    <script>
        function togglePassword() {
            const type = document.getElementById('showPassword').checked ? 'text' : 'password';
            document.getElementById('password').type = type;
            document.getElementById('confirm_password').type = type;
        }

        function mjCycleTheme() {
            var themes = ['light', 'dark'];
            var icons  = { light: '☀️', dark: '🌙' };
            var cur    = document.documentElement.getAttribute('data-theme') || 'light';
            if (!themes.includes(cur)) cur = 'light';
            var next   = themes[(themes.indexOf(cur) + 1) % 2];
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('mj_theme', next);
            var btn = document.getElementById('mjThemeBtn');
            if (btn) btn.textContent = icons[next];
        }

        window.addEventListener('DOMContentLoaded', function(){
            var icons = { light: '☀️', dark: '🌙' };
            var cur   = localStorage.getItem('mj_theme') || 'light';
            if (!['light','dark'].includes(cur)) { cur = 'light'; localStorage.setItem('mj_theme','light'); }
            document.documentElement.setAttribute('data-theme', cur);
            var btn = document.getElementById('mjThemeBtn');
            if (btn) btn.textContent = icons[cur];

            // Phone live validation
            var phoneInput = document.getElementById('phoneInput');
            var phoneHint  = document.getElementById('phoneHint');
            if (phoneInput && phoneHint) {
                phoneInput.addEventListener('input', function() {
                    var len = this.value.length;
                    if (len === 0) {
                        phoneHint.style.color = '';
                        phoneHint.textContent = '📱 Must be exactly 11 digits (e.g. 09171234567)';
                    } else if (len < 11) {
                        phoneHint.style.color = '#FF9800';
                        phoneHint.textContent = '📱 ' + len + ' / 11 digits entered';
                    } else {
                        phoneHint.style.color = '#4CAF50';
                        phoneHint.textContent = '✅ Phone number looks good!';
                    }
                });
            }
        });
    </script>
</body>
</html>