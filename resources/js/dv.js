/**
 * Set admin JTK
 */
$(document).ready(function () {
    //do stuff
    $('input.lang').thaana();
    $('textarea.lang').thaana();

    $('[data-make-dhivehi]').on('click', function (e) {
        e.preventDefault();

        setTimeout(function () {
            $('input.lang').thaana();
        }, 300);
    });

});
