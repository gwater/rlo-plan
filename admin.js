var column_names      = ['name',  'password', 'role'];
var column_widths     = ['100px', '100px',    '40px'];
var column_maxLengths = [ 20,      20,         5];

var url = 'post.php?poster=user';

function make_selector(cell) {
    var selector = newElement('select');
    for (var i in roles){
        var option = newElement('option');
        option.setAttribute('value', roles[i]);
        option.innerHTML = roles[i];
        selector.appendChild(option);
        if (roles[i] == cell.innerHTML) {
            selector.selectedIndex = i;
        }
    }
    cell.innerHTML = '';
    cell.appendChild(selector);
}

function modify_user(button) {
    hide_buttons(button);
    show_buttons(button.nextSibling.nextSibling);
    var row = button.parentNode.parentNode;
    for (var i = 0; i < row.childNodes.length - 1; i++) {
        var cell = row.childNodes[i];
        if (column_names[i] == 'role') {
            value = cell.innerHTML;
            make_selector(cell);
        } else {
            make_textbox(cell, i);
            value = cell.lastChild.value;
        }
        make_backup(cell, value);
    }
}

function delete_user(button) {
    hide_buttons(button.previousSibling);
    var row = button.parentNode.parentNode;
    var msg = 'action=delete&id=' + row.id.substr(4); // remove 'user' from 'user123'
    var status = newElement('span');
    status.textContent = 'Löschen...';
    row.lastChild.appendChild(status);
    var request = send_msg(msg);
    if (request) {
        if (request.status == 200) {
            remove(row);
        } else {
            show_buttons(button.previousSibling);
            status.textContent = request.status + ' - ' + request.statusText + ': ' + request.responseText;
            setTimeout(function() {
                fade_out(status);
            }, 3000, 'JavaScript');
        }
    }
}

function save_user(button) {
    hide_buttons(button);
    var row = button.parentNode.parentNode;
    var msg = '';
    var contentHasChanged = false;
    for (var i = 0; i < row.childNodes.length - 1; i++) {
        var cell = row.childNodes[i];
        if (column_names[i] == 'role') {
            var newvalue = roles[cell.firstChild.selectedIndex];
        } else {
            var newvalue = cell.firstChild.value;
        }
        if (newvalue != cell.lastChild.textContent) {
            cell.textContent = newvalue;
            contentHasChanged = true;
            msg += '&' + column_names[i] + '=' + cell.textContent;
        } else {
            cell.textContent = cell.lastChild.textContent;
        }
    }
    if (contentHasChanged) {
        msg = 'action=update&id=' + row.id.substr(4) + msg;
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
                    fade_out(status);
                }, 3000, 'JavaScript');
            }
        }
    }
    show_buttons(button.previousSibling.previousSibling);
}

function save_new_user(button) {
    hide_buttons(button);
    var row = button.parentNode.parentNode;
    var msg = '';
    for (var i = 0; i < row.childNodes.length - 1; i++) {
        var cell = row.childNodes[i];
        if (column_names[i] == 'role') {
            cell.textContent = roles[cell.firstChild.selectedIndex];
        } else {
            cell.textContent = cell.firstChild.value;
        }
        msg += '&' + column_names[i] + '=' + cell.textContent;
    }
    msg = 'action=add' + msg;
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
                fade_out(status);
            }, 3000, 'JavaScript');
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
        cancel_editing(button.nextSibling);
    }

}

function delete_new_user(button) {
    var row = button.parentNode.parentNode;
    remove(row);
}

function add_new_user(button) {
    var row = newElement('tr');

    // data cells:
    for (var i = 0; i < column_widths.length; i++) {
        var cell = newCell('');
        if (column_names[i] == 'role') {
            make_selector(cell);
        } else {
            make_textbox(cell, i);
        }
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
    var cancel_button = newButton('Abbrechen', cancel_editing);
    cancel_button.style.display = 'none';
    button_cell.appendChild(cancel_button);
    row.appendChild(button_cell);

    return row;
}

function insertUsers(users) {
    var table = document.getElementById('ovp_table_users');
    for (i in users) {
        table.appendChild(users[i]);
    }
}

function init_admin() {
    table = document.getElementById('ovp_table_users');
    table.parentNode.insertBefore(newButton('+ Benutzer', add_new_user), table.nextSibling);
    fill_in_data();
}
