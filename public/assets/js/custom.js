// $(document).ready(function() {
//
//     $("li:first-child").addClass("first");
//     $("li:last-child").addClass("last");
//
//     $('[href="#"]').attr("href", "javascript:;");
//     $('.menu-Bar').click(function() {
//         $(this).toggleClass('open');
//         $('.menuWrap').toggleClass('open');
//         $('body').toggleClass('ovr-hiddn');
//         $('body').toggleClass('overflw');
//     });
//
//     $('.index-slider').slick({
//         dots: false,
//         arrows: false,
//         infinite: true,
//         speed: 300,
//         slidesToShow: 4,
//         slidesToScroll: 1,
//         autoplay: true,
//         autoplaySpeed: 2000,
//         responsive: [
//             {
//                 breakpoint: 825,
//                 settings: {
//                     slidesToShow: 1,
//                     slidesToScroll: 1,
//                     infinite: true,
//                     dots: false,
//                     arrows:false
//                 }
//             },
//         ]
//     });
//
//
//     $('.m-silder').slick({
//         dots: true,
//         arrows: true,
//         infinite: true,
//         fade: true,
//         speed: 300,
//         slidesToShow: 1,
//         slidesToScroll: 1,
//         autoplay: true,
//         autoplaySpeed: 2000,
//         responsive: [
//             {
//                 breakpoint: 825,
//                 settings: {
//                     slidesToShow: 1,
//                     slidesToScroll: 1,
//                     infinite: true,
//                     dots: true,
//                     arrows:false
//                 }
//             },
//         ]
//     });
//
//     $('.product-slid').slick({
//         dots: false,
//         arrows: false,
//         infinite: true,
//         speed: 300,
//         slidesToShow: 5,
//         slidesToScroll: 1,
//         autoplay: true,
//         autoplaySpeed: 2000,
//         responsive: [
//             {
//                 breakpoint: 825,
//                 settings: {
//                     slidesToShow: 1,
//                     slidesToScroll: 1,
//                     infinite: true,
//                     dots: false,
//                     arrows:false
//                 }
//             },
//         ]
//     });
//
//     $('.client-slider').slick({
//         dots: false,
//         arrows: true,
//         infinite: true,
//         speed: 300,
//         slidesToShow: 1,
//         slidesToScroll: 1,
//         autoplay: true,
//         autoplaySpeed: 2000,
//         responsive: [
//             {
//                 breakpoint: 825,
//                 settings: {
//                     slidesToShow: 1,
//                     slidesToScroll: 1,
//                     infinite: true,
//                     dots: false,
//                     arrows:false
//                 }
//             },
//         ]
//     });
//
//     $('.event-slider').slick({
//         dots: false,
//         arrows: true,
//         speed: 300,
//         slidesToShow: 3,
//         slidesToScroll: 1,
//         autoplay: false,
//         autoplaySpeed: 2000,
//         centerMode: true,
//         responsive: [
//             {
//                 breakpoint: 825,
//                 settings: {
//                     slidesToShow: 1,
//                     slidesToScroll: 1,
//                     infinite: true,
//                     dots: false,
//                     arrows:false
//
//                 }
//             },
//         ]
//     });
//
//
// // counter javascript start
//
//     $('.count').each(function () {
//         $(this).prop('Counter',0).animate({
//             Counter: $(this).text()
//         }, {
//             duration: 4000,
//             easing: 'swing',
//             step: function (now) {
//                 $(this).text(Math.ceil(now));
//             }
//         });
//     });
//
// // counter javascript end
//
//
//     $('ul.faq-ul li.active div').slideDown();
//     $('ul.faq-ul li h4').click(function() {
//         $('ul.faq-ul li').removeClass('active');
//         $(this).parent('li').addClass('active');
//         $('ul.faq-ul li div').slideUp();
//         $(this).parent('li').find('div').slideDown();
//     });
//
//     $('.faq-ul>li').click(function(){
//         $(this).addClass('active');
//         $(this).siblings().removeClass('active');
//     });
//
//     $('.fancybox-media').fancybox({
//         openEffect: 'none',
//         closeEffect: 'none',
//         helpers: {
//             media: {}
//         }
//     });
//
//     $('.searchBtn').click(function() {
//         $('.searchWrap').addClass('active');
//         $('.overlay').fadeIn('active');
//         $('.searchWrap input').focus();
//         $('.searchWrap input').focusout(function(e) {
//             $(this).parents().removeClass('active');
//             $('.overlay').fadeOut('active');
//             $('body').removeClass('ovr-hiddn');
//
//         });
//     });
//
// });


$(window).on('load', function() {
    var currentUrl = window.location.href.substr(window.location.href.lastIndexOf("/") + 1);
    $('ul.menu li a').each(function() {
        var hrefVal = $(this).attr('href');
        if (hrefVal == currentUrl) {
            $(this).removeClass('active');
            $(this).closest('li').addClass('active')
            $('ul.menu li.first').removeClass('active');
        }
    })

});

// tabing

$('[data-targetit]').on('click', function(e) {
    $(this).addClass('current');
    $(this).siblings().removeClass('current');
    var target = $(this).data('targetit');
    $('.' + target).siblings('[class^="box-"]').hide();
    $('.' + target).fadeIn();
});


// sticky header

$(window).scroll(function() {
    if ($(this).scrollTop() > 500){
        $('').addClass("box-visable");
    }
    else{
        $('').removeClass("box-visable");
    }
});


// slider additional js for tabbing
$("[data-targetit]").on("click", function (e) {
    $(".test").slick("setPosition");
});


// clock javascript

// function clock() {
//     let date = new Date ();
//     let h = date.getHours ();
//     let m = date.getMinutes ();
//     let s = date.getSeconds ();
//     let aa;
//     if (h > 12) {
//         h -= 12
//         aa = "pm";
//     } else {
//         aa = "am"
//     }
//
//     if (h < 10) {
//         h = `0${h}`
//     }
//
//     if (m < 10) {
//         m = `0${m}`
//     }
//
//     if (s < 10) {
//         s = `0${s}`
//     }
//     let time = `${h} : ${m} : ${s} ${aa}`
//     document.querySelector ( ".x" ).innerText = time;
//     setTimeout ( clock, 1000 );
// }

// clock ()



// Search header

// const searchBarContainerEl = document.querySelector(".search-bar-container");
//
// const magnifierEl = document.querySelector(".magnifier");
//
// magnifierEl.addEventListener("click", () => {
//     searchBarContainerEl.classList.toggle("active");
// });




// Modal


$('[data-id="model"]').on('click', function(){
    var thisModel = $(this).data('target');
    $(thisModel).show();
    $(thisModel).find('[data-close="model"]').click(function(){
        $(thisModel).hide();
    });
    $(window).click(function(event){
        if('#'+event.target.id == thisModel){
            $(thisModel).hide();
        }
    });
});


$('[data-id="model2"]').on('click', function(){
    var thisModel = $(this).data('target');
    $(thisModel).show();
    $(thisModel).find('[data-close="model2"]').click(function(){
        $(thisModel).hide();
    });
    $(window).click(function(event){
        if('#'+event.target.id == thisModel){
            $(thisModel).hide();
        }
    });
});








// function openNav(id) {
//     document.getElementById("mySidenav"+id).style.width = "250px";
// }
function openNav(id) {
     var otherSideNavs = document.querySelectorAll('.sideNav:not(#mySidenav' + id + ')');
        otherSideNavs.forEach(function(nav) {
            nav.style.width = "0";
        });


        var sideNav = document.getElementById("mySidenav" + id);
        var currentWidth = sideNav.style.width;

        if (currentWidth === "250px") {
            sideNav.style.width = "0";
        } else {
            sideNav.style.width = "250px";
        }
    }
    function closeNav(id) {
        document.getElementById("mySidenav"+id).style.width = "0";
    }




    function readURL(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#imagePreview').css('background-image', 'url('+e.target.result +')');
                $('#imagePreview').hide();
                $('#imagePreview').fadeIn(650);
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    $("#imageUpload").change(function() {
        readURL(this);
    });
    $('.submenu').hide();
    $('li.menu-items').on('click', function(){

        $(this).find('.submenu').slideToggle('slow');
        $(this).find('.menu-items-link').toggleClass('active');

    })
