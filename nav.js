// nav.js — SkillSwap
// Exposes: navigate(), initNav()
// Relies on classes already in style.css — injects nothing new.

function navigate(page) {
    window.location.href = page;
}

// Prefixed _ to avoid clashing with each page's own escapeHtml()
function _navEscape(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        return m === '&' ? '&amp;' : m === '<' ? '&lt;' : '&gt;';
    });
}

// Call at the bottom of every page.
// activePage: 'home' | 'profile' | 'login' | ''
function initNav(activePage) {
    var root = document.getElementById('navbar-root');
    if (!root) return;

    var loggedIn = !!localStorage.getItem('token');
    var username = localStorage.getItem('username') || 'Profile';

    // Top bar — uses .navbar and .nav-right from style.css
    var top = '<nav class="navbar">'
            +   '<h2 onclick="navigate(\'index.html\')">SkillSwap</h2>'
            +   '<div class="nav-right">';

    if (loggedIn) {
        top += _navBtn('Home',                    "navigate('index.html')",   activePage === 'home');
        top += _navBtn(_navEscape(username),       "navigate('profile.html')", activePage === 'profile');
        top += _navBtn('Logout',                   '_navLogout()',             false);
    } else {
        top += _navBtn('Home',  "navigate('index.html')",  activePage === 'home');
        top += _navBtn('Login', "navigate('login.html')",  activePage === 'login');
    }

    top += '</div></nav>';

    // Bottom bar — uses .bottom-nav from style.css (hidden on desktop automatically)
    var bottom = '<div class="bottom-nav"><div class="bottom-nav-inner">';

    if (loggedIn) {
        bottom += _bottomItem('Home',    "navigate('index.html')",   activePage === 'home');
        bottom += _bottomItem('Profile', "navigate('profile.html')", activePage === 'profile');
        bottom += _bottomItem('Logout',  '_navLogout()',             false);
    } else {
        bottom += _bottomItem('Home',  "navigate('index.html')",  activePage === 'home');
        bottom += _bottomItem('Login', "navigate('login.html')",  activePage === 'login');
    }

    bottom += '</div></div>';

    root.innerHTML = top + bottom;
}

// Builds a desktop nav link. Uses .nav-link and .active from style.css
function _navBtn(label, action, isActive) {
    var cls = 'nav-link' + (isActive ? ' active' : '');
    return '<span class="' + cls + '" onclick="' + action + '">' + label + '</span>';
}

// Builds a mobile bottom tab. Uses .bottom-nav-item and .active from style.css
function _bottomItem(label, action, isActive) {
    var cls = 'bottom-nav-item' + (isActive ? ' active' : '');
    return '<div class="' + cls + '" onclick="' + action + '">'
         +   '<div class="nav-label">' + label + '</div>'
         + '</div>';
}

// Prefixed _ to avoid clashing with profile.html's own logout()
function _navLogout() {
    var userId = localStorage.getItem('userId');
    if (userId) {
        try { navigator.sendBeacon('User_list.php?offline=' + userId); } catch(e) {}
    }
    fetch('Users.php?action=logout', { method: 'POST' }).catch(function() {});
    localStorage.clear();
    navigate('login.html');
}
