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

function hide_buttons(button) {
    button.style.display = 'none';
    button.nextSibling.style.display = 'none';
}

function show_buttons(button) {
    button.style.display = 'inline-block';
    button.nextSibling.style.display = 'inline-block';
}

function send_msg(msg) {
    var request = null;
    if (window.XMLHttpRequest) {
        request = new XMLHttpRequest();
    } else if (window.ActiveXObject) {
        request = new ActiveXObject('Microsoft.XMLHTTP');
    }
    if (request) {
        // url is defined in entry.js and admin.js
        request.open('POST', url, false);
        request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        request.send(msg);
    }
    return request;
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
            e.parentNode.removeChild(e);
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
