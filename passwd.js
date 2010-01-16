var url;

function validate() {
    var status = newStatus('', document.getElementById('ovp_status'));
    if (this.newpwd.value !== this.newpwd2.value) {
        remove_status(status);
        status.textContent = 'ungleiche Passwörter';
        return false;
    }
    url = this.action;
    var xhr = newXHR();
    if (!xhr) {
        this.onsubmit = null;
        return true;
    }
    status.textContent = 'Ändern...';
    xhr.open('POST', url, false);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.send('xhr=true&oldpwd=' + this.oldpwd.value + '&newpwd=' + this.newpwd.value);
    remove_status(status, xhr);
    return false;
}

function init_pwd() {
    document.getElementById('ovp_table_password').parentNode.onsubmit = validate;
}
