var url;
var form;

function validate() {
    var status = newStatus('', document.getElementById('ovp_status'));
    if (form.newpwd.value !== form.newpwd2.value) {
        remove_status(status);
        status.textContent = 'ungleiche Passwörter';
        return false;
    }
    status.textContent = 'Wird geändert...';
    send_msg('xhr=true&oldpwd=' + form.oldpwd.value + '&newpwd=' + form.newpwd.value, function(xhr) {
        remove_status(status, xhr);
    }, function() {
        form.onsubmit = null;
        form.submit();
    });
    return false;
}

function init_pwd() {
    form = document.getElementById('ovp_table_password').parentNode;
    form.onsubmit = validate;
    url = form.action;
}

document.addEventListener("DOMContentLoaded", init_pwd, false);
