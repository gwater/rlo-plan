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

var column_titles     = ['Uhrzeit', 'Klasse', 'Fach',    'Dauer',    'Vertretung', 'Änderung', 'Alter Raum', 'Neuer Raum'];
var column_names      = ['time',    'course', 'subject', 'duration', 'sub',        'change',   'oldroom',    'newroom'];
var column_widths     = ['40px',    '40px',   '40px',    '25px',     '150px',      '245px',    '40px',       '40px'];
var column_maxLengths = [ 5,         5,        5,         3,          30,           40,         5,            5];

var day_names = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
var relative_day_names = ['heute', 'morgen', 'übermorgen']; // array index corresponds to distance from today

function modify_entry(button) {
    hide_buttons(button);
    show_buttons(button.nextSibling.nextSibling);
    var row = button.parentNode.parentNode;
    for (var i = 0; i < row.childNodes.length - 1; i++) {
        var cell = row.childNodes[i];
        make_textbox(cell, i);
        make_backup(cell, cell.lastChild.value);
    }
}

function remove_row(row) {
    if (row.parentNode.childNodes.length == 2) {
        var teacher = row.parentNode.parentNode;
        var day = teacher.parentNode;
        if (day.childNodes.length == 4) {
            remove(day);
        } else {
            remove(teacher);
        }
    } else {
        remove(row);
    }
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
            remove_row(row);
        } else {
            show_buttons(button.previousSibling);
            status.textContent = request.status + ' - ' + request.statusText + ': ' + request.responseText;
            setTimeout(function() {
                fade_out(status);
            }, 3000, 'JavaScript');
        }
    }
}

function save_entry(button) {
    var row = button.parentNode.parentNode;
    var teacher = row.parentNode.parentNode;
    var day = teacher.parentNode;
    var date = parse_date(day.firstChild.textContent);
    if (date === null) {
        alert('Bitte berichtigen Sie erst das Datum.');
        return;
    }
    date = format_date_server(date);
    var msg = '&date=' + date + '&teacher=' + teacher.firstChild.textContent;
    var contentHasChanged = false;
    for (var i = 0; i < row.childNodes.length - 1; i++) {
        var cell = row.childNodes[i];
        var new_value = cell.firstChild.value;
        if (i == 0) {
            new_value = parse_time(new_value);
            if (new_value === null) {
                alert('Die Uhrzeit ist fehlerhaft.');
                return;
            }
            hide_buttons(button);
        }
        if (new_value != cell.lastChild.textContent) {
            cell.textContent = new_value;
            contentHasChanged = true;
        } else {
            cell.textContent = cell.lastChild.textContent;
        }
        msg += '&' + column_names[i] + '=' + cell.textContent;
    }
    if (contentHasChanged) {
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
                    fade_out(status);
                }, 3000, 'JavaScript');
            }
        }
    }
    show_buttons(button.previousSibling.previousSibling);
}

function save_new_entry(button) {
    var row = button.parentNode.parentNode;
    var teacher = row.parentNode.parentNode;
    var day = teacher.parentNode;
    var date = parse_date(day.firstChild.textContent);
    if (date === null) {
        alert('Bitte berichtigen Sie erst das Datum.');
        return;
    }
    date = format_date_server(date);
    var msg = '&date=' + date + '&teacher=' + teacher.firstChild.textContent;
    for (var i = 0; i < row.childNodes.length - 1; i++) {
        var cell = row.childNodes[i];
        var new_value = cell.firstChild.value;
        if (i == 0) {
            new_value = parse_time(new_value);
            if (new_value === null) {
                alert('Die Uhrzeit ist fehlerhaft.');
                return;
            }
            hide_buttons(button);
        }
        cell.textContent = new_value;
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
        save_entry(button);
    }
    button.nextSibling.innerHTML = 'Abbrechen';
    button.nextSibling.onclick = function() {
        cancel_editing(button.nextSibling);
    }
}

function delete_new_entry(button) {
    var row = button.parentNode.parentNode;
    remove_row(row);
}

function add_new_entry(button) {
    var row = newElement('tr');

    // data cells:
    for (var i = 0; i < column_widths.length; i++) {
        var cell = newCell('');
        make_textbox(cell, i);
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

    button.previousSibling.appendChild(row);
    row.firstChild.firstChild.focus();
}

function add_teacher(button) {
    var day = button.parentNode;
    var teacher = newTeacher('Neuer Lehrer', []);
    day.insertBefore(teacher, day.lastChild);
    teacher.childNodes[1].value = '';
    teacher.firstChild.onclick();
}

function parse_time(time) {
    var matches = time.match(/^(\d\d?).(\d\d?)$/);
    if (matches !== null) {
        for (var i = 1; i <= 2; i++) {
            if (matches[i].length == 1) {
                matches[i] = '0' + matches[i];
            }
        }
        return matches[1] + ':' + matches[2];
    }
    return null;
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
        for (var i = 0; i < day_names.length; i++) {
            if (lower_date == day_names[i].substr(0, lower_date.length).toLowerCase()) {
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

// DayOfWeek, DD.MM.YYYY
function format_date_client(d) {
    var day = d.getDate() + '';
    if (day.length == 1) {
        day = '0' + day;
    }
    var month = (d.getMonth() + 1) + '';
    if (month.length == 1) {
        month = '0' + month;
    }
    return day_names[d.getDay()] + ', ' + day + '.' + month + '.' + d.getFullYear();
}

// YYYY-MM-DD
function format_date_server(d) {
    return d.getFullYear() + '-' + (d.getMonth() + 1) + '-' + d.getDate();
}

function get_default_date() {
    var d = new Date();
    var last_day = document.getElementById('ovp').lastChild.previousSibling;
    if (last_day) {
        var last_date = parse_date(last_day.firstChild.textContent);
        if (last_date && last_date.getDate() >= d.getDate()) {
            d = last_date;
            d.setDate(d.getDate() + 1);
        }
    }
    if (d.getDay() == 0) { // skip Sunday
        d.setDate(d.getDate() + 1);
    } else if (d.getDay() == 6) { // skip Saturday
        d.setDate(d.getDate() + 2);
    }
    return format_date_client(d);
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
    var cancel_button = newButton('Abbrechen', cancel_editing);
    cancel_button.style.display = 'none';
    button_cell.appendChild(cancel_button);
    row.appendChild(button_cell);

    return row;
}

function save_teacher(teacher) {
    var date = parse_date(teacher.parentNode.firstChild.textContent);
    if (date === null) {
        alert('Bitte berichtigen Sie erst das Datum.');
        return;
    }
    date = format_date_server(date);
    var rows = teacher.childNodes[2].childNodes;
    for (var i = 1; i < rows.length; i++) {
        var row = rows[i];
        if (row.id) {
            var cells = row.childNodes;
            var msg = 'action=update&id=' + row.id.substr(5) + '&date=' + date + '&teacher=' + teacher.firstChild.textContent;
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
                    fade_out(status);
                } else {
                    status.textContent = request.status + ' - ' + request.statusText + ': ' + request.responseText;
                    status.style.background = 'lightred';
                    fade_out(status);
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
        header.style.display = 'none';
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
            textbox['create_entry_on_first_blur'] = true;
        }
        textbox['last_key'] = key - 9;
        return true;
    }
    textbox.onkeyup = function() {
        textbox['last_key'] = 0;
    }
    textbox.onblur = function() {
        textbox.style.display = 'none';
        var header = this.previousSibling;
        var table = this.nextSibling;
        if (this.value != header.textContent && this.value != '') {
            header.textContent = this.value;
            if (table.childNodes.length > 1) {
                save_teacher(this.parentNode);
            }
        }
        if (table.childNodes.length == 1 && this['create_entry_on_first_blur']) {
            this.parentNode.lastChild.onclick();
        }
        header.style.display = 'block';
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
        header.style.display = 'none';
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
            textbox['create_teacher_on_first_blur'] = true;
        }
        textbox['last_key'] = key - 9;
        return true;
    }
    textbox.onkeyup = function() {
        textbox['last_key'] = 0;
    }
    textbox.onblur = function() {
        var header = this.previousSibling;
        if (this.value != '') {
            var new_date = parse_date(this.value);
            if (new_date) {
                var new_header = format_date_client(new_date);
                if (header.textContent != new_header) {
                    header.textContent = new_header;
                    var teachers = this.parentNode.getElementsByTagName('section');
                    for (var i = 0; i < teachers.length; i++) {
                        save_teacher(teachers[i]);
                    }
                }
            } else {
                header.innerHTML = '<span class="ovp_error">' + this.value + '</span>';
            }
        }
        textbox.style.display = 'none';
        header.style.display = 'block';
        if (this.parentNode.childNodes.length == 3 && this['create_teacher_on_first_blur']) {
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
