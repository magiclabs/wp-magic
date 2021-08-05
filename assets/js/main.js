let magic = new Magic(magic_wp.publishable_key);
const queryString = window.location.search;
const urlParams = new URLSearchParams(queryString);

/* Login Handler */
const handleLogin = async (e) => {
    e.preventDefault();
    document.body.innerHTML += '<div id="loader" class="loader"></div>';
    const email = new FormData(e.target).get("email");
    const redirectURI = `${window.location.origin + magic_wp.redirect_uri}`;
    if (email) {
        const req = magic.auth.loginWithMagicLink({ email, redirectURI });
        var el = document.getElementById('loader');
        req
            .on('email-sent', () => {
                el.remove();
            })
            .then(DIDToken => {
                document.body.innerHTML += '<div id="loader" class="loader"></div>';
                MagicSignInDefault(DIDToken);
                el.remove();
            })
            .once('email-not-deliverable', () => {
                alert('Error to send email');
                el.remove();
            })
            .catch(error => {
                alert('Error to get token');
                console.log(error);
                el.remove();
            })
            .on('error', error => {
                alert('Error to get token');
                console.log(error);
                el.remove();
            });
    }
    const isLoggedIn = await magic.user.isLoggedIn();
    if (isLoggedIn) {
        handleLogout(magic_wp.redirect_uri);
    }
};

// Magic Sign-in default
const MagicSignInDefault = async (didToken) => {
    var el = document.getElementById('loader');
    fetch(magic_wp.api_uri + 'magic/v1/auth/', {
        headers: {
            Authorization: 'Bearer ' + didToken
        }
    })
        .then(response => {
            if (response.status !== 200) {
                el.remove();
                alert('Error to validate token');
                throw new Error('Problem! Status Code: ' + response.status);
            }
            response.text().then(function (text) {
                var link = text.replace(/"/g, "");
                document.location.href = link;
            });
        })
        .catch(function (err) {
            el.remove();
            alert('Error to send validate token');
            console.log('Error: ', err);
        });
};

// Magic Sign-in
const MagicSignIn = async () => {
    try {
        await magic.auth.loginWithCredential();
    } catch {
        window.location.href = magic_wp.redirect_uri;
    }
    const isLoggedIn = await magic.user.isLoggedIn();
    if (isLoggedIn) {
        var el = document.getElementById('loader');
        const didToken = await magic.user.getIdToken();
        fetch(magic_wp.api_uri + 'magic/v1/auth/', {
            headers: {
                Authorization: 'Bearer ' + didToken
            }
        })
            .then(response => {
                if (response.status !== 200) {
                    el.remove();
                    alert('Error to validate token');
                    throw new Error('Problem! Status Code: ' + response.status);
                }
                response.text().then(function (text) {
                    var link = text.replace(/"/g, "");
                    document.location.href = link;
                });
            })
            .catch(function (err) {
                el.remove();
                alert('Error to send validate token');
                console.log('Error: ', err);
            });
    }
};

if (urlParams.get('magic_credential') !== null) {
    document.addEventListener("DOMContentLoaded", function (event) {
        document.body.innerHTML = '<div class="loader"></div>';
    });
    MagicSignIn();
}


jQuery(function ($) {
    var wooLogout = $(".woocommerce-MyAccount-navigation-link--customer-logout a");
    if (wooLogout.length > 0) {
        var wcLink = wooLogout.attr('href');
        wooLogout.click(function (e) {
            e.preventDefault();
            handleLogout(wcLink);

        });
    }
    var wpLogout = $("#wp-admin-bar-logout a");
    if (wpLogout.length > 0) {
        var wpLink = wpLogout.attr('href');
        wpLogout.click(function (e) {
            e.preventDefault();
            handleLogout(wpLink);
        });
    }
    if ($('#magic-already-logged').length > 0) {
        document.location.href = magic_wp.redirect_uri;
    }
});

/* Logout Handler */
const handleLogout = async (link) => {
    await magic.user.logout();
    document.location.href = link;
};