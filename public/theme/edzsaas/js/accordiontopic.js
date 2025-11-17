/*
    Accordiontopic.js
    Copyright: 2022 themesedzsaas
*/
var acc = document.getElementsByClassName("accordionedzsaas");
var i;
for (i = 0; i < acc.length; i++) {
    acc[i].addEventListener('click', function() {
        this.classList.toggle('edzsaasactive');
        var paneledzsaas = this.nextElementSibling;
        if (paneledzsaas.style.display === 'block') {
            paneledzsaas.style.display = 'none';
        } else {
            paneledzsaas.style.display = 'block';
        }
    });
}
