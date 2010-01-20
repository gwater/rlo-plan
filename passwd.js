var url;

function validate() {
    form = this;
    var status = newStatus('', document.getElementById('ovp_status')); // TODO: change "status" to something else
    if (form.newpwd.value !== form.newpwd2.value) {
        remove_status(status);
        status.textContent = 'ungleiche Passwörter';
        return false;
    }
    url = form.action;
    status.textContent = 'Ändern...';
    send_msg('xhr=true&oldpwd=' + form.oldpwd.value + '&newpwd=' + form.newpwd.value, function() {
        remove_status(status, this);
    }, function() {
        form.onsubmit = null;
        form.submit();
    });
    return false;
}

function init_pwd() {
    document.getElementById('ovp_table_password').parentNode.onsubmit = validate;
}
