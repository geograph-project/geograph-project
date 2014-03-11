jQuery(function() {
    var showtip = function (url, elem,xoffset) {

            var pos = elem.offset();
            var y_pos = pos.top - 12;
            var x_pos = pos.left + (xoffset || ((elem.width()/2) + 20));

            var tip_arrow = $('<img>').attr('src', './images/tip_arrow.png').addClass('tip_arrow');
            var tip_box = $('<div>').addClass('tip_box');
            var tip_shadow = $('<div>').addClass('tip_shadow');
            var tip_content = $('<div>').addClass('tip_content').load(url, function() {
                tip_content.prepend('<a href="#" class="tip_close">x</a>');
            });

            var the_tip = tip_box.append(tip_arrow).append(tip_content).prepend(tip_shadow);
            the_tip.css({
                "top":y_pos + "px",
                "left":x_pos + "px"
            }).addClass(elem.data('id'));
            $('.tip_box').remove();
            $('body').append(the_tip.hide().fadeIn());
            $('.' + elem.data('id') + ' .tip_shadow').css({
                "height":$('.' + elem.data('id')).height() + 5
            });
    },
    getHelpTips = (function() {
        var dfd = $.Deferred(),
            requested = false,
            namespace = $('meta[name=tip-namespace]').attr('content');
        return function() {
            if (namespace && dfd.state() != 'resolved' && !requested)
                requested = $.ajax({
                    url: "ajax.php/help/tips/" + namespace,
                    dataType: 'json',
                    success: function (json_config) {
                        dfd.resolve(json_config);
                    }
                });
            return dfd;
        }
    })();

    //Generic tip.
    $('.tip')
    .each(function(i, e) {
        e.rel = 'tip-' + i;
    })
    .live('click mouseover', function(e) {
        e.preventDefault();
        var id = this.rel;
        var elem = $(this);

        elem.data('id',id);
        elem.data('timer',0);
        if ($('.' + id).length == 0) {
            if (e.type=='mouseover') {
                // wait about 1 sec - before showing the tip - mouseout kills
                // the timeout
                elem.data('timer',setTimeout(function() {
                    showtip('ajax.php/content/'+elem.attr('href'),elem);i
                },750));
            } else {
                showtip('ajax.php/content/'+elem.attr('href'),elem);
            }
        }
    })
    .live('mouseout', function(e) {
        clearTimeout($(this).data('timer'));
    });

    $('.help-tip')
    .live('mouseover click', function(e) {
        e.preventDefault();

        var elem = $(this),
            pos = elem.offset(),
            y_pos = pos.top - 8,
            x_pos = pos.left + elem.width() + 16,
            tip_arrow = $('<img>')
                .attr('src', './images/tip_arrow.png')
                .addClass('tip_arrow'),
            tip_box = $('<div>')
                .addClass('tip_box'),
            tip_content = $('<div>')
                .append('<a href="#" class="tip_close"><i class="icon-remove-circle"></i></a>')
                .addClass('tip_content'),
            the_tip = tip_box
                .append(tip_content.append(tip_arrow))
                .css({
                    "top":y_pos + "px",
                    "left":x_pos + "px"
                }),
            tip_timer = setTimeout(function() {
                $('.tip_box').remove();
                $('body').append(the_tip.hide().fadeIn());
                if ($(window).width() < tip_content.outerWidth() + the_tip.position().left) {
                    the_tip.css({'left':x_pos-tip_content.outerWidth()-40+'px'});
                    tip_box.addClass('right');
                    tip_arrow.addClass('flip-x');
                }
            }, 500);

        elem.live('mouseout', function() {
            clearTimeout(tip_timer);
        });

        getHelpTips().then(function(tips) {
            var section = tips[elem.attr('href').substr(1)];
            if (!section) {
                elem.remove();
                clearTimeout(tip_timer);
                return;
            }
            tip_content.append(
                $('<h1>')
                    .append('<i class="icon-info-sign faded"> ')
                    .append(section.title)
                ).append(section.content);
            if (section.links) {
                var links = $('<div class="links">');
                $.each(section.links, function(i,l) {
                    var icon = l.href.match(/^http/)
                        ? 'icon-external-link' : 'icon-share-alt';
                    links.append($('<div>')
                        .append($('<a>')
                            .html(l.title)
                            .prepend('<i class="'+icon+'"></i> ')
                            .attr('href', l.href).attr('target','_blank'))
                    );
                });
                tip_content.append(links);
            }
        });
        $('.tip_shadow', the_tip).css({
            "height":the_tip.height() + 5
        });
    });

    //faq preview tip
    $('.previewfaq').live('mouseover', function(e) {
        e.preventDefault();
        var elem = $(this);

        var vars = elem.attr('href').split('=');
        var url = 'ajax.php/kb/faq/'+vars[1];
        var id='faq'+vars[1];
        var xoffset = 100;

        elem.data('id',id);
        elem.data('timer',0);
        if($('.' + id).length == 0) {
            if(e.type=='mouseover') {
                 /* wait about 1 sec - before showing the tip - mouseout kills the timeout*/
                 elem.data('timer',setTimeout(function() { showtip(url,elem,xoffset);},750))
            }else{
                showtip(url,elem,xoffset);
            }
        }
    }).live('mouseout', function(e) {
        clearTimeout($(this).data('timer'));
    });

    //Ticket preview
    $('.ticketPreview').live('mouseover', function(e) {
        e.preventDefault();
        var elem = $(this);

        var vars = elem.attr('href').split('=');
        var url = 'ajax.php/tickets/'+vars[1]+'/preview';
        var id='t'+vars[1];
        var xoffset = 80;


        elem.data('id',id);
        elem.data('timer',0);
        if($('.' + id).length == 0) {
            if(e.type=='mouseover') {
                 /* wait about 1 sec - before showing the tip - mouseout kills the timeout*/
                 elem.data('timer',setTimeout(function() { showtip(url,elem,xoffset);},750))
            }else{
                showtip(url,elem,xoffset);
            }
        }
    }).live('mouseout', function(e) {
        clearTimeout($(this).data('timer'));
    });

    $('body')
    .delegate('.tip_close', 'click', function(e) {
        e.preventDefault();
        $(this).parent().parent().remove();
    });
});
