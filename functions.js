
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
        request = new ActiveXObject("Microsoft.XMLHTTP");
    }
    if (request) {
        request.open('POST', 'post.php', false);
        request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        request.send(msg);
    }
    return request;
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
    }, 100, "JavaScript");
}
