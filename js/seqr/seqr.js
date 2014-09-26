(function() {
    window.seqr = { id: '' };

    var seqrBasePath = window.location.pathname
        .substring(0, window.location.pathname.indexOf('/seqr/')) + '/seqr/payment';

    window.seqrStatusUpdated = function(data) {
        if (!data || ! data.status || data.status === 'ISSUED') return;
        window.location.href = seqrBasePath + '/submit/id/' + window.seqr.id;
    };
}());