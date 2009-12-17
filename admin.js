
var column_names_user      = ['name',  'password', 'role'];
var column_widths_user     = ['100px', '100px',    '40px'];
var column_maxLengths_user = [ 20,      20,         5];



function newUser(id, name, password, role) {
    var row = newElement('tr');
    row.id = 'user' + id;

    // data cells:
    row.appendChild(newCell(name));
    row.appendChild(newCell(password));
    row.appendChild(newCell(role));

    // button cell:
    var button_cell = newElement('td');
    var mod_button = newButton('Bearbeiten', modify_user);
    button_cell.appendChild(mod_button);
    var del_button = newButton('Löschen', delete_user);
    button_cell.appendChild(del_button);
    var save_button = newButton('Speichern', save_user);
    save_button.style.display = 'none';
    button_cell.appendChild(save_button);
    var cancel_button = newButton('Abbrechen', cancel_editing_user);
    cancel_button.style.display = 'none';
    button_cell.appendChild(cancel_button);
    row.appendChild(button_cell);

    return row;
}

function insertUsers(users) {
    for (i in users) {
        var table = document.getElementById('ovp_table_users');
        table.appendChild(users[i]);
    }
}

function modify_user(button) {
    hide_buttons(button);
    show_buttons(button.nextSibling.nextSibling);
    var row = button.parentNode.parentNode;
    for (var i = 0; i < row.childNodes.length - 1; i++) {
        var cell = row.childNodes[i];
        var textbox = newElement('input');
        textbox.type = 'text';
        textbox.value = cell.textContent;
        textbox.maxLength = column_maxLengths_user[i];
        textbox.style.width = column_widths_user[i];
        cell.innerHTML = '';
        cell.appendChild(textbox);
        var backup = newElement('span');
        backup.style.display = 'none';
        backup.textContent = textbox.value;
        cell.appendChild(backup);
    }
}

function delete_user(button) {
    hide_buttons(button.previousSibling);
    var row = button.parentNode.parentNode;
    var msg = '&asset=user&action=delete&id=' + row.id.substr(4); // remove 'user' from 'user123'
    var status = newElement('span');
    status.textContent = 'Löschen...';
    row.lastChild.appendChild(status);
    var request = send_msg(msg);
    if (request) {
        if (request.status == 200) {
                row.parentNode.removeChild(row);
        } else {
            show_buttons(button.previousSibling);
            status.textContent = request.status + ' - ' + request.statusText + ': ' + request.responseText;
            setTimeout(function() {
                fadeOut(status);
            }, 3000);
        }
    }
}


// FIXME don't change password to '***'
function save_user(button) {
    hide_buttons(button);
    var row = button.parentNode.parentNode;
    var msg = '';
    var contentHasChanged = false;
    for (var i = 0; i < row.childNodes.length - 1; i++) {
        var cell = row.childNodes[i];
        if (cell.firstChild.value != cell.lastChild.textContent) {
            cell.textContent = cell.firstChild.value;
            contentHasChanged = true;
        } else {
            cell.textContent = cell.lastChild.textContent;
        }
        msg += '&' + column_names_user[i] + '=' + cell.textContent;
    }
    if (contentHasChanged) {
        var row = button.parentNode.parentNode;
        msg = 'asset=user&action=update&id=' + row.id.substr(4) + msg;
        var status = newElement('span');
        status.textContent = 'Speichern...';
        row.lastChild.appendChild(status);
        var request = send_msg(msg);
        if (request) {
            if (request.status == 200) {
                row.lastChild.removeChild(status);
            } else {
                status.textContent = request.status + ' - ' + request.statusText + ': ' + request.responseText;
                setTimeout(function() {
                    fadeOut(status);
                }, 3000);
            }
        }
    }
    show_buttons(button.previousSibling.previousSibling);
}

function cancel_editing_user(button) {
    var saveButton = button.previousSibling;
    hide_buttons(saveButton);
    show_buttons(saveButton.previousSibling.previousSibling);
    var row = button.parentNode.parentNode;
    for (var i = 0; i < row.childNodes.length - 1; i++) {
        var cell = row.childNodes[i];
        cell.textContent = cell.lastChild.textContent;
    }
}

function add_new_user(button) {
    var row = newElement('tr');

    // FIXME: make role <select>...
    // data cells:
    for (var i = 0; i < column_widths_user.length; i++) {
        var cell = newCell('');
        var textbox = newElement('input');
        textbox.type = 'text';
        textbox.maxLength = column_maxLengths_user[i];
        textbox.style.width = column_widths_user[i];
        cell.appendChild(textbox);
        row.appendChild(cell);
    }

    // button cell:
    var button_cell = newElement('td');
    var mod_button = newButton('Bearbeiten', modify_user);
    mod_button.style.display = 'none';
    button_cell.appendChild(mod_button);
    var del_button = newButton('Löschen', delete_user);
    del_button.style.display = 'none';
    button_cell.appendChild(del_button);
    var save_button = newButton('Speichern', save_new_user);
    button_cell.appendChild(save_button);
    var cancel_button = newButton('Löschen', delete_new_user);
    button_cell.appendChild(cancel_button);
    row.appendChild(button_cell);

    document.getElementById('ovp_table_users').appendChild(row);
    row.firstChild.firstChild.focus();
}

function save_new_user(button) {
    hide_buttons(button);
    var row = button.parentNode.parentNode;
    var msg = '';
    for (var i = 0; i < row.childNodes.length - 1; i++) {
        var cell = row.childNodes[i];
        cell.textContent = cell.firstChild.value;
        msg += '&' + column_names_user[i] + '=' + cell.textContent;
    }
    msg = 'asset=user&action=add' + msg;
    var status = newElement('span');
    status.textContent = 'Speichern...';
    row.lastChild.appendChild(status);
    var request = send_msg(msg);
    if (request) {
        if (request.status == 200) {
            row.lastChild.removeChild(status);
            row.id = 'entry' + request.responseText;
        } else {
            status.textContent = request.status + ' - ' + request.statusText + ': ' + request.responseText;
            setTimeout(function() {
                fadeOut(status);
            }, 3000, "JavaScript");
            row.lastChild.firstChild.onclick();
            return;
        }
    }
    show_buttons(button.previousSibling.previousSibling);
    button.onclick = function() {
        save_user(button);
    }
    button.nextSibling.innerHTML = 'Abbrechen';
    button.nextSibling.onclick = function() {
        cancel_editing_user(button.nextSibling);
    }

}

function delete_new_user(button) {
    var row = button.parentNode.parentNode;
    row.parentNode.removeChild(row);
}

function init_admin() {
    table = document.getElementById('ovp_table_users');
    table.parentNode.insertBefore(newButton('+ Benutzer', add_new_user), table.nextSibling);
    fill_in_data();
}
