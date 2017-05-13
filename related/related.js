function relatedOpen(that) {
    var linkid = that.dataset.linkid;
    var list = document.getElementById('related_links_'+linkid);
    var html = list.outerHTML;
    var popin = document.getElementById('related_popin');
    popin.innerHTML = html;
    var bodyRect = document.body.getBoundingClientRect(),
    var elemRect = that.getBoundingClientRect(),
    var offsetY  = elemRect.top - bodyRect.top + 24;
    var offsetX  = elemRect.left - bodyRect.left;// + popin.clientWidth;
    if (popin.dataset.theme === 'material') {
        offsetX -= popin.clientWidth;
    }
    if (popin.dataset.linkid == linkid && popin.style.visibility == '') {
        popin.style.visibility = 'hidden';
    } else {
        popin.dataset.linkid = linkid;
        popin.style.visibility = '';
        popin.style.left = offsetX+'px';
        popin.style.top = offsetY+'px';
    }
    return false;
}