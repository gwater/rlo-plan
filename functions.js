// this code is static

var column_titles     = ['Uhrzeit', 'Klasse', 'Fach',    'Dauer',    'Vertretung', 'Änderung', 'Alter Raum', 'Neuer Raum'];
var column_names      = ['time',    'course', 'subject', 'duration', 'sub',        'change',   'oldroom',    'newroom'];
var column_widths     = ['40px',    '40px',   '40px',    '25px',     '150px',      '245px',    '40px',       '40px'];
var column_maxLengths = [ 5,         5,        5,         3,          30,           40,         5,            5];

function newElement(type) {
    return document.createElement(type);
}

function newCell(value) {
    var cell = newElement('td');
    cell.innerHTML = value;
    return cell;
}

function newButton(caption, action) {
    var button = newElement('button');
    button.type = 'button';
    button.innerHTML = caption;
    button.onclick = function() {
        action(this);
    };
    return button;
}

function hideButtons(button) {
    button.style.display = 'none';
    button.nextSibling.style.display = 'none';
}

function showButtons(button) {
    button.style.display = 'inline-block';
    button.nextSibling.style.display = 'inline-block';
}

function modify_entry(button) {
    hideButtons(button);
    showButtons(button.nextSibling.nextSibling);
    var row = button.parentNode.parentNode;
    var firstRow = row.parentNode.firstChild;
    for (var i = 0; i < row.childNodes.length - 1; i++) {
        var cell = row.childNodes[i];
        var textbox = newElement('input');
        textbox.type = 'text';
        textbox.value = cell.textContent;
        textbox.maxLength = column_maxLengths[i];
        textbox.style.width = column_widths[i];
        cell.innerHTML = '';
        cell.appendChild(textbox);
        var backup = newElement('span');
        backup.style.display = 'none';
        backup.textContent = textbox.value;
        cell.appendChild(backup);
    }
}

function getXMLHttp() {
    if (window.XMLHttpRequest) {
        return new XMLHttpRequest();
    } else if (window.ActiveXObject) {
        return new ActiveXObject("Microsoft.XMLHTTP");
    }
    alert('Ihr Browser unterstützt kein XMLHttpRequest.');
    return false;
}

function delete_entry(button) {
    var request = getXMLHttp();
    if (request) {
        hideButtons(button.previousSibling);
        var row = button.parentNode.parentNode;
        var msg = 'action=delete&id=' + row.id.substr(5); // remove 'entry' from 'entry123'
        request.open('POST', 'post.php', false);
        request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        var status = newElement('span');
        status.textContent = 'Löschen...';
        row.lastChild.appendChild(status);
        request.send(msg);
        if (request.status == 200) {
            if (row.parentNode.childNodes.length == 2) {
                var teacher = row.parentNode.parentNode;
                var day = teacher.parentNode;
                if (day.childNodes.length == 4) {
                    day.parentNode.removeChild(day);
                } else {
                    teacher.parentNode.removeChild(teacher);
                }
            } else {
                row.parentNode.removeChild(row);
            }
        } else {
            showButtons(button.previousSibling);
            status.textContent = request.status + ' - ' + request.statusText + ': ' + request.responseText;
            setTimeout(function() {
                fadeOut(status);
            }, 3000);
        }
    }
}

function delete_new_entry(button) {
    var row = button.parentNode.parentNode;
    if (row.parentNode.childNodes.length == 2) {
        var teacher = row.parentNode.parentNode;
        var day = teacher.parentNode;
        if (day.childNodes.length == 4) {
            day.parentNode.removeChild(day);
        } else {
            teacher.parentNode.removeChild(teacher);
        }
    } else {
        row.parentNode.removeChild(row);
    }
}

function save_entry(button) {
    hideButtons(button);
    var row = button.parentNode.parentNode;
    var teacher = row.parentNode.parentNode;
    var day = teacher.parentNode;
    var msg = '&day=' + day.firstChild.textContent + '&teacher=' + teacher.firstChild.textContent;
    var contentHasChanged = false;
    for (var i = 0; i < row.childNodes.length - 1; i++) {
        var cell = row.childNodes[i];
        if (cell.firstChild.value != cell.lastChild.textContent) {
            cell.textContent = cell.firstChild.value;
            contentHasChanged = true;
        } else {
            cell.textContent = cell.lastChild.textContent;
        }
        msg += '&' + column_names[i] + '=' + cell.textContent;
    }
    if (contentHasChanged) {
        var request = getXMLHttp();
        if (request) {
            var row = button.parentNode.parentNode;
            msg = 'action=update&id=' + row.id.substr(5) + msg;
            request.open('POST', 'post.php', false);
            request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            var status = newElement('span');
            status.textContent = 'Speichern...';
            row.lastChild.appendChild(status);
            request.send(msg);
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
    showButtons(button.previousSibling.previousSibling);
}

function fadeOut(e) {
    if (!e.style.opacity) {
        e.style.opacity = 1.0;
    }
    setTimeout(function() {
        if (e.style.opacity > 0) {
            e.style.opacity -= 0.1;
            fadeOut(e);
        } else {
            e.parentNode.removeChild(e);
        }
    }, 100);
}

function save_new_entry(button) {
    hideButtons(button);
    var row = button.parentNode.parentNode;
    var teacher = row.parentNode.parentNode;
    var day = teacher.parentNode;
    var msg = '&day=' + day.firstChild.textContent + '&teacher=' + teacher.firstChild.textContent;
    for (var i = 0; i < row.childNodes.length - 1; i++) {
        var cell = row.childNodes[i];
        cell.textContent = cell.firstChild.value;
        msg += '&' + column_names[i] + '=' + cell.textContent;
    }
    var request = getXMLHttp();
    if (request) {
        var row = button.parentNode.parentNode;
        msg = 'action=add' + msg;
        request.open('POST', 'post.php', false);
        request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        var status = newElement('span');
        status.textContent = 'Speichern...';
        row.lastChild.appendChild(status);
        request.send(msg);
        if (request.status == 200) {
            row.lastChild.removeChild(status);
            row.id = 'entry' + request.responseText;
        } else {
            status.textContent = request.status + ' - ' + request.statusText + ': ' + request.responseText;
            setTimeout(function() {
                fadeOut(status);
            }, 3000);
        }
    }
    showButtons(button.previousSibling.previousSibling);
    button.onclick = function() {
        save_entry(button);
    }
    button.nextSibling.innerHTML = 'Abbrechen';
    button.nextSibling.onclick = function() {
        cancel_editing_entry(button.nextSibling);
    }
}

function cancel_editing_entry(button) {
    var saveButton = button.previousSibling;
    hideButtons(saveButton);
    showButtons(saveButton.previousSibling.previousSibling);
    var row = button.parentNode.parentNode;
    for (var i = 0; i < row.childNodes.length - 1; i++) {
        var cell = row.childNodes[i];
        cell.textContent = cell.lastChild.textContent;
    }
}

function add_new_entry(button) {
    var row = newElement('tr');

    // data cells:
    for (var i = 0; i < column_widths.length; i++) {
        var cell = newCell('');
        var textbox = newElement('input');
        textbox.type = 'text';
        textbox.maxLength = column_maxLengths[i];
        textbox.style.width = column_widths[i];
        cell.appendChild(textbox);
        row.appendChild(cell);
    }

    // button cell:
    var button_cell = newElement('td');
    var mod_button = newButton('Bearbeiten', modify_entry);
    mod_button.style.display = 'none';
    button_cell.appendChild(mod_button);
    var del_button = newButton('Löschen', delete_entry);
    del_button.style.display = 'none';
    button_cell.appendChild(del_button);
    var save_button = newButton('Speichern', save_new_entry);
    button_cell.appendChild(save_button);
    var cancel_button = newButton('Löschen', delete_new_entry);
    button_cell.appendChild(cancel_button);
    row.appendChild(button_cell);

    button.parentNode.querySelector('.ovp_table').appendChild(row);
    row.firstChild.firstChild.focus();
}

function add_teacher(button) {
    var day = button.parentNode;
    var teacher = newTeacher('Neuer Lehrer', []);
    day.insertBefore(teacher, day.lastChild);
    teacher.childNodes[1].value = '';
    teacher.firstChild.onclick();
}

function add_day(button) {
    var ovp = button.parentNode;
    var day = newDay('Neuer Tag', []);
    ovp.insertBefore(day, ovp.lastChild);
    day.childNodes[1].value = '';
    day.firstChild.onclick();
}

// 'id' is from the database
function newEntry(id, cols) {
    var row = newElement('tr');
    row.id = 'entry' + id;

    // data cells:
    for (i in cols) {
        row.appendChild(newCell(cols[i]));
    }

    // button cell:
    var button_cell = newElement('td');
    var mod_button = newButton('Bearbeiten', modify_entry);
    button_cell.appendChild(mod_button);
    var del_button = newButton('Löschen', delete_entry);
    button_cell.appendChild(del_button);
    var save_button = newButton('Speichern', save_entry);
    save_button.style.display = 'none';
    button_cell.appendChild(save_button);
    var cancel_button = newButton('Abbrechen', cancel_editing_entry);
    cancel_button.style.display = 'none';
    button_cell.appendChild(cancel_button);
    row.appendChild(button_cell);

    return row;
}

function newTeacher(name, entries) {
    var teacher = newElement('section');

    var header = newElement('h3');
    header.innerHTML = name;
    header.onclick = function() {
        this.style.display = 'none';
        var textbox = this.nextSibling;
        textbox.style.display = 'block';
        textbox.focus();
    }
    teacher.appendChild(header);

    var textbox = newElement('input');
    textbox.type = 'text';
    textbox.style.display = 'none';
    textbox.value = name;
    textbox.onblur = function() {
        this.style.display = 'none';
        var header = this.previousSibling;
        if (this.value != '') {
            header.innerHTML = this.value;
        }
        header.style.display = 'block';
        // TODO: tell server about this OR reupload all contained entries to server
    }
    teacher.appendChild(textbox);

    var table = newElement('table');
    table.setAttribute('class', 'ovp_table');
    var header_row = newElement('tr');
    for (var i = 0; i < column_titles.length; i++) {
        header_row.appendChild(newCell(column_titles[i]));
    }
    header_row.appendChild(newCell('Aktion'));
    table.appendChild(header_row);
    for (i in entries) {
        table.appendChild(entries[i]);
    }
    teacher.appendChild(table);

    var entry_button = newButton('+ Eintrag', add_new_entry);
    teacher.appendChild(entry_button);

    return teacher;
}

function newDay(title, teachers) {
    var day = newElement('section');

    var header = newElement('h2');
    header.innerHTML = title;
    header.onclick = function() {
        this.style.display = 'none';
        var textbox = this.nextSibling;
        textbox.style.display = 'block';
        textbox.focus();
    }
    day.appendChild(header);

    var textbox = newElement('input');
    textbox.type = 'text';
    textbox.style.display = 'none';
    textbox.value = title;
    textbox.onblur = function() {
        this.style.display = 'none';
        var header = this.previousSibling;
        if (this.value != '') {
            header.innerHTML = this.value;
        }
        header.style.display = 'block';
        // TODO: tell server about this OR reupload all contained entries to server
    }
    day.appendChild(textbox);

    for (i in teachers) {
        day.appendChild(teachers[i]);
    }
    day.appendChild(newButton('+ Lehrer', add_teacher));

    return day;
}

function insertDays(days) {
    var ovp = document.getElementById('ovp');
    for (i in days) {
        ovp.insertBefore(days[i], ovp.lastChild);
    }
}

function init() {
    document.getElementById('ovp').appendChild(newButton('+ Tag', add_day));
    fill_in_data();
}
