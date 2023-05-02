window.channel_prefix = 'task_manager';
window.Notifications = {
    init: function(){

    },

    initSocket: function(_channel, _event, callback){
        const channelName = `${window.channel_prefix}:${_channel}`;
        const $channel = window.Echo.channel(channelName);
        if(!Array.isArray(_event)) {
            _event = [_event];
        }
        _event.map(e => $channel.listen(e, (data) => {
            if(typeof callback === 'function') {
                callback({...data, event: e});
            }
        }));

    }

}

$(document).ready(function () {
    Notifications.init();
});
