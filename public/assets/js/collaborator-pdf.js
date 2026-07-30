(function () {
    'use strict';

    var modal = document.getElementById('collaborator-pdf-modal');
    var frame = document.getElementById('collaborator-pdf-modal-frame');
    var title = document.getElementById('collaborator-pdf-modal-title');
    var openLink = document.getElementById('collaborator-pdf-modal-open');

    if (!modal || !frame || !title || !openLink) {
        return;
    }

    modal.addEventListener('show.bs.modal', function (event) {
        var trigger = event.relatedTarget;
        var url = trigger ? trigger.getAttribute('data-pdf-url') : '';
        var collaboratorName = trigger ? trigger.getAttribute('data-pdf-title') : '';

        title.textContent = collaboratorName || 'Apresentação';
        openLink.href = url || '#';
        frame.title = collaboratorName ? 'PDF de ' + collaboratorName : 'PDF do colaborador';
        frame.src = url ? url + '#view=FitH' : 'about:blank';
    });

    modal.addEventListener('hidden.bs.modal', function () {
        frame.src = 'about:blank';
        openLink.href = '#';
    });
}());
