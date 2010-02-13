var url;
var form;

function validate() {
    var status = newStatus('', document.getElementById('ovp_status'));
    if (!check_pwds()) {
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

function check_pwds() {
    var same = form.newpwd.value === form.newpwd2.value;
    form.submit.disabled = same ? null : 'disabled';
    form.submit.value = same ? 'Ändern' : 'Wiederholung falsch';
    return same;
}

function init_pwd() {
    form = document.getElementById('ovp_table_password').parentNode;
    url = form.action;
    form.onsubmit = validate;
    form.newpwd.onkeyup = check_pwds;
    form.newpwd2.onkeyup = check_pwds;
}

document.addEventListener('DOMContentLoaded', init_pwd, false);
