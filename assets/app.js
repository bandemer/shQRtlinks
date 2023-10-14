
import './styles/app.scss';

const bootstrap = require('bootstrap');

//Test URLs of links
document.getElementById('testLinkUrl').addEventListener('click',
function (){
        let lUrl = document.getElementById('linkUrl').value;
        if (lUrl != '') {
              window.open(lUrl, '_blank', 'noopener');
        }
        return false;
});
