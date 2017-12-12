require('../css/app.scss');
let $ = require('jquery');
require('bootstrap-sass');

$('#getWatchlist').on('click', (event) => {
    $.ajax('/scanners/watchlist').then(() => {
        location.reload();
    });
});
