/**
 * This file is part of RLO-Plan.
 *
 * Copyright 2009, 2010 Tillmann Karras, Josua Grawitter
 *
 * RLO-Plan is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * RLO-Plan is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with RLO-Plan.  If not, see <http://www.gnu.org/licenses/>.
 */

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
