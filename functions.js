var column_titles     = ['Uhrzeit', 'Klasse', 'Fach',    'Dauer',    'Vertretung', 'Änderung', 'Alter Raum', 'Neuer Raum'];
var column_names      = ['time',    'course', 'subject', 'duration', 'sub',        'change',   'oldroom',    'newroom'];
var column_widths     = ['40px',    '40px',   '40px',    '25px',     '150px',      '245px',    '40px',       '40px'];
var column_maxLengths = [ 5,         5,        5,         3,          30,           40,         5,            5];

var day_names = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
var relative_day_names = ['heute', 'morgen', 'übermorgen']; // array index corresponds to distance from today

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
    //button.type = 'button'; // not supported by IE8
    button.innerHTML = caption;
    button.onclick = function() {
        action(this);
    };
    return button;
}

function hide_buttons(button) {
    button.style.display = 'none';
    button.nextSibling.style.display = 'none';
}

function show_buttons(button) {
    button.style.display = 'inline-block';
    button.nextSibling.style.display = 'inline-block';
}

function modify_entry(button) {
    hide_buttons(button);
    show_buttons(button.nextSibling.nextSibling);
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

function send_msg(msg) {
    var request = null;
    if (window.XMLHttpRequest) {
        request = new XMLHttpRequest();
    } else if (window.ActiveXObject) {
        request = new ActiveXObject("Microsoft.XMLHTTP");
    }
    if (request) {
        request.open('POST', 'post.php', false);
        request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        request.send(msg);
    }
    return request;
}

function delete_entry(button) {
    hide_buttons(button.previousSibling);
    var row = button.parentNode.parentNode;
    var msg = 'action=delete&id=' + row.id.substr(5); // remove 'entry' from 'entry123'
    var status = newElement('span');
    status.textContent = 'Löschen...';
    row.lastChild.appendChild(status);
    var request = send_msg(msg);
    if (request) {
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
            show_buttons(button.previousSibling);
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
    hide_buttons(button);
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
        var row = button.parentNode.parentNode;
        msg = 'action=update&id=' + row.id.substr(5) + msg;
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
    hide_buttons(button);
    var row = button.parentNode.parentNode;
    var teacher = row.parentNode.parentNode;
    var day = teacher.parentNode;
    var msg = '&day=' + day.firstChild.textContent + '&teacher=' + teacher.firstChild.textContent;
    for (var i = 0; i < row.childNodes.length - 1; i++) {
        var cell = row.childNodes[i];
        cell.textContent = cell.firstChild.value;
        msg += '&' + column_names[i] + '=' + cell.textContent;
    }
    var row = button.parentNode.parentNode;
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
                fadeOut(status);
            }, 3000);
            row.lastChild.firstChild.onclick();
            return;
        }
    }
    show_buttons(button.previousSibling.previousSibling);
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
    hide_buttons(saveButton);
    show_buttons(saveButton.previousSibling.previousSibling);
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

function parse_date(date) {
    var matches = date.match(/(\d\d?).(\d\d?).((\d\d)?\d\d)$/);
    if (matches !== null) {
        var year = matches[3];
        if (year.length == 2) {
            year = '20' + year;
        }
        return new Date(year, matches[2] - 1, matches[1], 0, 0, 0, 0);
    }
    var lower_date = date.toLowerCase();
    for (var i = 0; i < relative_day_names.length; i++) {
        if (lower_date == relative_day_names[i]) {
            var result = new Date();
            result.setDate(result.getDate() + i);
            return result;
        }
    }
    if (date.length <= 10) { // length of 'Donnerstag'
        var next_day_of_week = lower_date.substr(0, 2);
        for (var i = 0; i < day_names.length; i++) {
            if (next_day_of_week == day_names[i].substr(0, 2).toLowerCase()) {
                var result = new Date();
                var today = result.getDay();
                if (i < today) {
                    i += 7;
                }
                result.setDate(result.getDate() + i - today);
                return result;
            }
        }
    }
    return null;
}

function format_date(d) {
    return day_names[d.getDay()] + ', ' + d.getDate() + '.' + (d.getMonth() + 1) + '.' + d.getFullYear();
}

function get_default_date() {
    var d = new Date();
    var last_day = document.querySelector('#ovp').lastChild.previousSibling;
    if (last_day) {
        var last_date = parse_date(last_day.firstChild.textContent);
        if (last_date) {
            last_date.setDate(last_date.getDate() + 1);
            if (last_date > d) {
                d = last_date;
            }
        }
    }
    return format_date(d);
}

function add_day(button) {
    var ovp = button.parentNode;
    var date = get_default_date();
    var day = newDay(date, []);
    ovp.insertBefore(day, ovp.lastChild);
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

function save_teacher(teacher) {
    var day = teacher.parentNode.firstChild.textContent;
    var rows = teacher.querySelector('.ovp_table').childNodes;
    for (var i = 1; i < rows.length; i++) {
        var row = rows[i];
        if (row.id) {
            var cells = row.childNodes;
            var msg = 'action=update&id=' + row.id.substr(5) + '&day=' + day + '&teacher=' + teacher.firstChild.textContent;
            for (var j = 0; j < cells.length - 1; j++) {
                msg += '&' + column_names[j] + '=' + cells[j].textContent;
            }
            var status = newElement('span');
            status.textContent = 'Speichern...';
            row.lastChild.appendChild(status);
            var request = send_msg(msg);
            if (request) {
                if (request.status == 200) {
                    status.textContent = 'OK';
                    status.style.background = 'lightgreen';
                    fadeOut(status);
                } else {
                    status.textContent = request.status + ' - ' + request.statusText + ': ' + request.responseText;
                    status.style.background = 'lightred';
                    fadeOut(status);
                }
            }
        }
    }
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
    textbox['last_key'] = 0;
    textbox.onkeydown = function(e) {
        var key;
        if (window.event) {
            key = event.keyCode;
        } else if (e) {
            key = e.which;
        }
        if (key == 9 && this['last_key'] == 0) {
            this['create_entry_on_first_blur'] = true;
        }
        this['last_key'] = key - 9;
        return true;
    }
    textbox.onkeyup = function() {
        this['last_key'] = 0;
    }
    textbox.onblur = function() {
        this.style.display = 'none';
        var header = this.previousSibling;
        if (this.value != '') {
            header.textContent = this.value;
        }
        header.style.display = 'block';
        var table = this.parentNode.querySelector('.ovp_table');
        if (table.childNodes.length == 1 && this['create_entry_on_first_blur']) {
            this.parentNode.lastChild.onclick();
        } else {
            save_teacher(this.parentNode);
        }
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
        textbox.select();
    }
    day.appendChild(header);

    var textbox = newElement('input');
    textbox.type = 'text';
    textbox.style.display = 'none';
    textbox.value = title;
    textbox['last_key'] = 0;
    textbox.onkeydown = function(e) {
        var key;
        if (window.event) {
            key = event.keyCode;
        } else if (e) {
            key = e.which;
        }
        if (key == 9 && this['last_key'] == 0) {
            this['create_teacher_on_first_blur'] = true;
        }
        this['last_key'] = key - 9;
        return true;
    }
    textbox.onkeyup = function() {
        this['last_key'] = 0;
    }
    textbox.onblur = function() {
        var header = this.previousSibling;
        var table = this.parentNode.querySelector('.ovp_table');
        if (this.value != '') {
            var new_date = parse_date(this.value);
            if (new_date) {
                var new_header = format_date(new_date);
                if (header.textContent != new_header) {
                    header.textContent = new_header;
                    if (table) {
                        var teachers = this.parentNode.getElementsByTagName('section');
                        for (var i = 0; i < teachers.length; i++) {
                            save_teacher(teachers[i]);
                        }
                    }
                }
            } else {
                header.innerHTML = '<span class="ovp_error">' + this.value + '</span>';
            }
        }
        this.style.display = 'none';
        header.style.display = 'block';
        if (table == null && this['create_teacher_on_first_blur']) {
            this.parentNode.lastChild.onclick();
        }
    }
    day.appendChild(textbox);

    for (i in teachers) {
        day.appendChild(teachers[i]);
    }
    day.appendChild(newButton('+ Lehrer', add_teacher));

    return day;
}

function insert_days(days) {
    var ovp = document.getElementById('ovp');
    for (i in days) {
        ovp.insertBefore(days[i], ovp.lastChild);
    }
}

function init() {
    document.getElementById('ovp').appendChild(newButton('+ Tag', add_day));
    fill_in_data();
}
