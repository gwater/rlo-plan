// this code is static

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
    for (var i = 0; i < row.childNodes.length - 1; i++) {
        var cell = row.childNodes[i];
        var textbox = newElement('input');
        textbox.type = 'text';
        textbox.value = cell.textContent;
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
    }
    alert('Ihr Browser unterstützt kein XMLHttpRequest und ich hab keinen Bock auf ActiveX.');
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
            // TODO: once most of the errors are fixed alert() the error message instead of adding it to the DOM
            button.parentNode.innerHTML += request.status + ' - ' + request.statusText + ': ' + request.responseText;
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
    showButtons(button.previousSibling.previousSibling);
    var row = button.parentNode.parentNode;
    var contentHasChanged = false;
    for (var i = 0; i < row.childNodes.length - 1; i++) {
        var cell = row.childNodes[i];
        if (cell.firstChild.value != cell.lastChild.textContent) {
            cell.textContent = cell.firstChild.value;
            contentHasChanged = true;
        } else {
            cell.textContent = cell.lastChild.textContent;
        }
    }
    if (contentHasChanged) {
        // TODO: send row to server
    }
}

function save_new_entry(button) {
    hideButtons(button);
    showButtons(button.previousSibling.previousSibling);
    var row = button.parentNode.parentNode;
    for (var i = 0; i < row.childNodes.length - 1; i++) {
        var cell = row.childNodes[i];
        cell.textContent = cell.firstChild.value;
    }
    // TODO: send row to server + get new id
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
    var cols = column_names();
    for (var i = 0; i < cols.length; i++) {
        var cell = newCell('');
        var textbox = newElement('input');
        textbox.type = 'text';
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
    teacher.firstChild.onclick();
}

function add_day(button) {
    var ovp = button.parentNode;
    var day = newDay('Neuer Tag', []);
    ovp.insertBefore(day, ovp.lastChild);
    day.firstChild.onclick();
}

// 'id' is from the database
function newEntry(id, cols) {
    var row = newElement('tr');
    row.id = 'entry' + id;

    // data cells:
    for (var i = 0; i < cols.length; i++) {
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
    var cols = column_names();
    for (var i = 0; i < cols.length; i++) {
        header_row.appendChild(newCell(cols[i]));
    }
    header_row.appendChild(newCell('Aktion'));
    table.appendChild(header_row);
    for (var i = 0; i < entries.length; i++) {
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

    for (var i = 0; i < teachers.length; i++) {
        day.appendChild(teachers[i]);
    }
    day.appendChild(newButton('+ Lehrer', add_teacher));

    return day;
}

function insertDays(days) {
    var ovp = document.getElementById('ovp');
    for (var i = 0; i < days.length; i++) {
        ovp.insertBefore(days[i], ovp.lastChild);
    }
}

function init() {
    document.getElementById('ovp').appendChild(newButton('+ Tag', add_day));
    fill_in_data();
}
