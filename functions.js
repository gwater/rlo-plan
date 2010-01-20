/**
 * This file is part of RLO-Plan.
 *
 * Copyright 2009 Tillmann Karras, Josua Grawitter
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

function remove(element) {
    element.parentNode.removeChild(element);
}

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

function newStatus(text, element) {
    var status = newElement('span');
    status.textContent = text;
    element.appendChild(status);
    return status;
}

function remove_status(status, xhr) {
    var success = false;
    if (xhr) {
        success = xhr.status == 200;
        status.textContent = success ? 'OK' : xhr.responseText;
    } else {
        status.textContent = 'Konnte nicht gespeichert werden';
    }
    status.style.background = success ? 'lightgreen' : '#FF8888';
    setTimeout(function() {
        fade_out(status);
    }, 2000, 'JavaScript');
}

function hide_buttons(button) {
    button.style.display = 'none';
    button.nextSibling.style.display = 'none';
}

function show_buttons(button) {
    button.style.display = 'inline-block';
    button.nextSibling.style.display = 'inline-block';
}

function newXHR() {
    var xhr = null;
    if (window.XMLHttpRequest) {
        xhr = new XMLHttpRequest();
    } else if (window.ActiveXObject) {
        xhr = new ActiveXObject('Microsoft.XMLHTTP');
    }
    return xhr;
}

function send_msg(msg) {
    var xhr = newXHR()
    if (xhr) {
        // url is defined in entry.js and admin.js
        xhr.open('POST', url, false);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send(msg);
    } else {
        alert('Diese Funktion erfordert einen neueren Browser.');
    }
    return xhr;
}

function fade_out(e) {
    if (!e.style.opacity) {
        e.style.opacity = 1.0;
    }
    setTimeout(function() {
        if (e.style.opacity > 0) {
            e.style.opacity -= 0.1;
            fade_out(e);
        } else {
            remove(e);
        }
    }, 100, 'JavaScript');
}

function make_textbox(cell, i) {
    var textbox = newElement('input');
    textbox.type = 'text';
    textbox.value = cell.textContent;
    textbox.maxLength = column_maxLengths[i];
    textbox.style.width = column_widths[i];
    cell.innerHTML = '';
    cell.appendChild(textbox);
}

function make_backup(cell, value) {
    var backup = newElement('span');
    backup.style.display = 'none';
    backup.textContent = value;
    cell.appendChild(backup);
}

function cancel_editing(button) {
    var saveButton = button.previousSibling;
    hide_buttons(saveButton);
    show_buttons(saveButton.previousSibling.previousSibling);
    var row = button.parentNode.parentNode;
    for (var i = 0; i < row.childNodes.length - 1; i++) {
        var cell = row.childNodes[i];
        cell.textContent = cell.lastChild.textContent;
    }
}
