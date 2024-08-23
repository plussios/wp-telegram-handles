(function (blocks, element) {
    var el = element.createElement;

    blocks.registerBlockType('thp/telegram-handles', {
        title: 'Telegram Handles',
        icon: 'admin-users',
        category: 'widgets',
        edit: function () {
            return el('p', {}, 'This block will display Telegram handles.');
        },
        save: function () {
            return null; // Renders using PHP
        },
    });
})(window.wp.blocks, window.wp.element);

