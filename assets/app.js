
import './styles/app.scss';

const bootstrap = require('bootstrap');

//Test URLs of links
if (document.getElementById('testLinkUrl') !== null) {
    document.getElementById('testLinkUrl').addEventListener('click',
    function (){
            let lUrl = document.getElementById('linkUrl').value;
            if (lUrl != '') {
                  window.open(lUrl, '_blank', 'noopener');
            }
            return false;
    });
}

async function changeStatus(url, linkid, token)
{
    url += '?linkid=' + linkid + '&token=' + token + '&to=';
    let to = 0;
    let clist = document.getElementById('linkstatus'+linkid).classList;
    if (clist.contains('text-danger')) {
        to = 1;
    }
    url += to;

    const response = await fetch(url);
    const json = await response.json();

    if (json.message == 'OK') {
        if (to == 0) {
            document.getElementById('linkstatus'+linkid).classList.remove('text-success');
            document.getElementById('linkstatus'+linkid).classList.add('text-danger');
        } else {
            document.getElementById('linkstatus'+linkid).classList.remove('text-danger');
            document.getElementById('linkstatus'+linkid).classList.add('text-success');
        }
    }
}
window.changeStatus = changeStatus;

async function fav(url, linkid, token)
{
    url += '?linkid=' + linkid + '&token=' + token + '&to=';
    let to = 0;
    let clist = document.getElementById('linkfav'+linkid).classList;
    if (clist.contains('bi-star')) {
        to = 1;
    }
    url += to;

    const response = await fetch(url);
    const json = await response.json();

    if (json.message == 'OK') {
        if (to == 0) {
            document.getElementById('linkfav'+linkid).classList.remove('bi-star-fill');
            document.getElementById('linkfav'+linkid).classList.add('bi-star');
        } else {
            document.getElementById('linkfav'+linkid).classList.remove('bi-star');
            document.getElementById('linkfav'+linkid).classList.add('bi-star-fill');
        }
    }
}


window.fav = fav;