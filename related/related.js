function relatedOpen(e) {
    var linkid = this.dataset.linkid;
    var listHTML = document.getElementById('related_links_'+linkid).outerHTML;
    var popin = document.getElementById('related_popin');
    var bodyRect = document.body.getBoundingClientRect();
    var elemRect = this.getBoundingClientRect();
    var offsetY  = elemRect.top - bodyRect.top + 24;
    var offsetX  = elemRect.left - bodyRect.left;
    var right = bodyRect.right - elemRect.right;
    
    if (offsetX > right) {
        offsetX -= popin.clientWidth - 24;
    }
    /*if (popin.dataset.theme === 'material') {
        offsetX -= popin.clientWidth;
    }*/
    popin.innerHTML = listHTML;
    if (popin.dataset.linkid == linkid && popin.style.visibility == '') {
        popin.style.visibility = 'hidden';
    } else {
        popin.dataset.linkid = linkid;
        popin.style.visibility = '';
        popin.style.left = offsetX+'px';
        popin.style.top = offsetY+'px';
    }
    e.preventDefault();
}

var open_related_links = document.getElementsByClassName('open_related');
for (i = 0; i < open_related_links.length; i++) {
    var link = open_related_links[i];
    if (link.addEventListener){
      link.addEventListener('click', relatedOpen, false);
    } else if (link.attachEvent) {
      link.attachEvent('onclick', relatedOpen);
    }
} 
