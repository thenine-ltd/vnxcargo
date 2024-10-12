/**
 * easyModal.js v1.3.2
 * A minimal jQuery modal that works with your CSS.
 * Author: Flavius Matis - http://flaviusmatis.github.com/
 * URL: https://github.com/flaviusmatis/easyModal.js
 *
 * Copyright 2012, Flavius Matis
 * Released under the MIT license.
 * http://flaviusmatis.github.com/license.html
 */

/* jslint browser: true */
/* global jQuery */

jQuery( function($) {
    'use strict';
    var chatyError;
    var forceSubmit = false;
    var whatsappStatus = false;
    var phoneStatus = false;
    var fbStatus = false;
    var smsStatus = false;
    var viberStatus = false;
    var phoneNumberStatus = false;
    function checkForDevices() {
        $(".chaty-popup").hide();
        if($("#cht-form .js-chanel-desktop").length == 0 || $("#cht-form .js-chanel-mobile").length == 0) {
            $("#no-device-popup").show();
            return false;
        } else if($("#cht-form .js-chanel-desktop:checked").length == 0 && $("#cht-form .js-chanel-mobile:checked").length == 0) {
            $("#device-popup").show();
            return false;
        } else {
            $("#channels-selected-list > li .cht-input-error").removeClass("cht-input-error");
            if(!$("#channels-selected-list > li.has-agent-view").length) {
                var inputError = 0;
                $("#channels-selected-list > li:not(.has-agent-view):not(#chaty-social-close)").find(".channels__input").each(function () {
                    if (jQuery.trim($(this).val()) == "") {
                        inputError++;
                        $(this).addClass("cht-input-error");
                    }
                });
                if (inputError == $("#channels-selected-list > li:not(.has-agent-view):not(#chaty-social-close)").find(".channels__input").length) {
                    if (!$("#chaty-social-Contact_Us").length) {
                        $("#no-device-value").show();
                        return false;
                    }
                }
            } else {
                var inputError = 0;
                $("#channels-selected-list > li:not(.has-agent-view):not(#chaty-social-close)").find(".channels__input").each(function () {
                    if (jQuery.trim($(this).val()) == "") {
                        inputError++;
                        $(this).addClass("cht-input-error");
                    }
                });
                $("#channels-selected-list > li.has-agent-view").find(".agent-input-value").each(function () {
                    if (jQuery.trim($(this).val()) == "") {
                        inputError++;
                        $(this).addClass("cht-input-error");
                    }
                });
                if (inputError == parseInt($("#channels-selected-list > li:not(.has-agent-view):not(#chaty-social-close)").find(".channels__input").length) + parseInt($("#channels-selected-list > li.has-agent-view").find(".agent-input-value").length)) {
                    if (!$("#chaty-social-Contact_Us").length) {
                        $("#no-device-value").show();
                        return false;
                    }
                }

                var isAgentEmpty = 0;
                $("#channels-selected-list > li.has-agent-view .chaty-agent-list .chaty-agent-name").each(function(){
                    if($.trim($(this).val()) == "") {
                        isAgentEmpty++;
                        $(this).addClass("cht-input-error");
                    }
                });
                if(isAgentEmpty) {
                    $("#agent-value-popup").show();
                    return false;
                }
            }
        }
        return checkForTriggers();
    }

    function checkForTriggers() {
        $(".chaty-popup").hide();
        if(!$("#trigger_on_time").is(":checked") && !$("#chaty_trigger_on_exit").is(":checked") && !$("#chaty_trigger_on_scroll").is(":checked")) {
            $("#trigger-popup").show();
            return false;
        }
        return checkForStatus();
    }

    function checkForStatus() {
        $(".chaty-popup").hide();
        if(!$(".cht_active").is(":checked")) {
            $("#status-popup").show();
            return false;
        }
        forceSubmit = true;
        $("#cht-form").trigger("submit");
        return true;
    }

    function checkForWhatsAppNumber() {

        if(isWhatsAppValidated) {
            return checkPreSettings();
        }

        $(".phone-number-list").html("");
        if($(".custom-channel-Whatsapp").length) {
            $("#whatsapp-message-popup").removeClass("has-multiple");
            if($("#Whatsapp_agent").length && $("#Whatsapp_agent").val() == 1) {
                let hasError = 0;
                if($("#agent-list-Whatsapp .custom-agent-channel-Whatsapp").length) {
                    $("#agent-list-Whatsapp .custom-agent-channel-Whatsapp").each(function(){
                        if($(this).val() != "" && $(this).val().indexOf("-0") != -1) {
                            hasError++;
                            let agentId = $(this).closest(".agent-channel-setting").data("item");
                            let phoneLabel = $(".phone-number-list").data("label");
                            let phoneAction = $(".phone-number-list").data("action");
                            let testTest = $(".phone-number-list").data("test");
                            let testLink = `https://wa.me/`+($.trim($(this).val()).replace(/[^a-zA-Z0-9 ]/g, '')).toHtmlEntities() ;
                            let phoneLink = `<a class="whatsapp-test-btn" target="_blank" href='${testLink}'>${testTest}</a>`;
                            let editLink = `<a data-index="${agentId}" class="remove-zero" href='javascript:;'>${phoneAction}</a>`;
                            let btnHtml = `<div data-index="${agentId}" class='number-list'>${phoneLabel}: <b>${$(this).val().toHtmlEntities()}</b> ${phoneLink} ${editLink}</div>`;
                            $(".phone-number-list").append(btnHtml);
                        }
                    });
                }
                if(hasError) {
                    $("#whatsapp-message-popup").show();
                    if($("#whatsapp-message-popup .number-list").length > 1) {
                        $("#whatsapp-message-popup").addClass("has-multiple");
                    }
                    return false;
                }
            } else {
                if($("#channel_input_Whatsapp").val() != "") {
                    if($("#channel_input_Whatsapp").val().indexOf("-0") != -1) {
                        let inputVal = $("#channel_input_Whatsapp").val();
                        let phoneLabel = $(".phone-number-list").data("label");
                        let testLink = `https://wa.me/`+($.trim(inputVal).replace(/[^a-zA-Z0-9 ]/g, '')).toHtmlEntities() ;
                        let phoneLink = `<a class="whatsapp-test-btn" target="_blank" href='${testLink}'>Test</a>`;
                        let btnHtml = `<div class='number-list is-not-agent'>${phoneLabel}: <b>${inputVal}</b> ${phoneLink}</div>`;
                        $(".phone-number-list").html(btnHtml);
                        $("#whatsapp-message-popup").show();
                        return false;
                    }
                }
            }
        }
        return checkPreSettings();
    }

    // https://wa.me/${whatsapp_phone_number ? whatsapp_phone_number.replace(/[^a-zA-Z0-9 ]/g, '') : ''}

    function checkPreSettings() {
        isWhatsAppValidated = true;
        if(!whatsappStatus) {
            whatsappStatus = true;
            var phoneNumberReg = /^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/;
            if ($("#cht-form #Whatsapp").length && $("#cht-form #Whatsapp").val() != "") {
                var InputVal = jQuery.trim($("#cht-form #Whatsapp").val());
                chatyError = check_for_number_chaty(InputVal, "Whatsapp");
                if(chatyError) {
                    $("#custom-message-popup .chaty-popup-header").text("Whatsapp number is not valid");
                    $("#custom-message-popup .chaty-popup-body").text("Seems like the WhatsApp number you're trying to enter isn't in the right syntax. Would you like to publish it anyway?");
                    $("#custom-message-popup").show();
                    return false;
                }
            }
        } else if(!phoneStatus) {
            phoneStatus = true;
            if ($("#cht-form #Phone").length && $("#cht-form #Phone").val() != "") {
                var InputVal = jQuery.trim($("#cht-form #Phone").val());
                chatyError = check_for_number_chaty(InputVal, "Phone");
                if(chatyError) {
                    $("#custom-message-popup .chaty-popup-header").text("Phone number is not valid");
                    $("#custom-message-popup .chaty-popup-body").text("Seems like the phone number you're trying to enter isn't in the right syntax. Would you like to publish it anyway?");
                    $("#custom-message-popup").show();
                    return false;
                }
            }
        } else if(!fbStatus) {
            fbStatus = true;
            if ($("#cht-form #Facebook_Messenger").length && $("#cht-form #Facebook_Messenger").val() != "") {
                var faceBookMeReg = /(?:http:\/\/)?m\.me\/(?:(?:\w)*#!\/)?(?:pages\/)?(?:[\w\-]*\/)*([\w\-]*)/;
                var faceBookReg = /(?:http:\/\/)?facebook\.com\/(?:(?:\w)*#!\/)?(?:pages\/)?(?:[\w\-]*\/)*([\w\-]*)/;
                var InputVal = jQuery.trim($("#Facebook_Messenger").val());
                $("#cht-form #Facebook_Messenger").val(InputVal);
                if (!faceBookReg.test(InputVal) && !faceBookMeReg.test(InputVal)) {
                    $("#custom-message-popup .chaty-popup-header").text("Facebook page's URL is not valid");
                    $("#custom-message-popup .chaty-popup-body").text("Please make sure your Facebook page's URL looks like, <br/>https://m.me/YOURPAGE");
                    $("#custom-message-popup").show();
                    return false;
                }
            }
        } else if(!smsStatus) {
            smsStatus = true;
            if ($("#cht-form #SMS").length && $("#cht-form #SMS").val() != "") {
                var InputVal = jQuery.trim($("#cht-form #SMS").val());
                chatyError = check_for_number_chaty(InputVal, "SMS");
                if(chatyError) {
                    $("#custom-message-popup .chaty-popup-header").text("SMS number is not valid");
                    $("#custom-message-popup .chaty-popup-body").text("Seems like the SMS number you're trying to enter isn't in the right syntax. Would you like to publish it anyway?");
                    $("#custom-message-popup").show();
                    return false;
                }
            }
        } else if(!viberStatus) {
            viberStatus = true;
            if ($("#cht-form #Viber").length && $("#cht-form #Viber").val() != "") {
                var InputVal = jQuery.trim($("#cht-form #Viber").val());
                chatyError = check_for_number_chaty(InputVal, "Viber");
                if(chatyError) {
                    $("#custom-message-popup .chaty-popup-header").text("Viber number is not valid");
                    $("#custom-message-popup .chaty-popup-body").text("Seems like the Viber number you're trying to enter isn't in the right syntax. Would you like to publish it anyway?");
                    $("#custom-message-popup").show();
                    return false;
                }
            }
        } else if(!phoneNumberStatus) {
            phoneNumberStatus = true;
            if($("#channels-selected-list .phone-number").length) {
                $("#channels-selected-list .phone-number").each(function(){
                    if(jQuery.trim($(this).val()) != '') {
                        var inputLen = (jQuery.trim($(this).val())).length;
                        if(inputLen > 13) {
                            $("#custom-message-popup .chaty-popup-header").text($(this).data("label")+" number is not valid");
                            $("#custom-message-popup .chaty-popup-body").text("Seems like the "+$(this).data("label")+" number you're trying to enter isn't valid. Would you like to publish it anyway?");
                            $("#custom-message-popup").show();
                            return false;
                        }
                    }
                });
            }
        }
        return checkForDevices();
    }

    $(window).on("load", function(){
        setTimeout(() => {
            $(".wp-editor-container iframe")
                .contents().find('body')
                .css({
                    backgroundColor: '#fff',
                    margin: 0,
                    padding: '0px 10px'
                });
        }, 500)
    });

    let isWhatsAppValidated = true;

    $(document).ready(function () {

        $(document).on("click", ".field-setting-col:not(.hide-label-setting) .field-label", function(e){
            e.preventDefault();
            $(this).closest(".label-flex").toggleClass("input-active");
        });

        $(document).on("click", ".chaty-switch-toggle", function(){
            setTimeout(function(){
                $(".chaty-field-setting").each(function(){
                    if($(this).is(":checked")) {
                        $(this).closest(".field-setting-col").find(".field-settings").addClass("active");
                        $(this).closest(".field-setting-col").removeClass("hide-label-setting");
                    } else {
                        $(this).closest(".field-setting-col").find(".field-settings").removeClass("active");
                        $(this).closest(".field-setting-col").addClass("hide-label-setting");
                    }
                });
            },100);
        });

        $(document).on("click", ".select-cta-fa-icon", function(e){
            //e.preventDefault();
            $("#chat-image-icon").removeClass("active");
            $("#chat-fa-icon").addClass("active");
            $(".widget-img-tooltip").removeClass("active");
            $(".widget-fa-icon-tooltip").addClass("active");
            $("input[name='widget_icon'][value='chat-fa-icon']").prop("checked", true);
        });

        var newIconLib1 = {
            "material":{
                "regular":{
                    "list-icon":"",
                    "icon-style":"mt-regular",
                    "icons":["some","some2"],
                }
            }
        }

        if($("#chat-fa-icon .icon-chat").length) {
            AestheticIconPicker({
                'selector': '#icon-select-chat', // must be an ID
                'onClick': '#select-cta-fa-icon',  // must be an ID
                "iconLibrary": newIconLib1
            });
        }

        $(document).on("click", ".select-cta-image", function(e){
            e.preventDefault();
            $("#chat-image-icon").addClass("active");
            $(".widget-img-tooltip").addClass("active");
            $("#chat-fa-icon").removeClass("active");
            $(".widget-fa-icon-tooltip").removeClass("active");
            $("input[name='widget_icon'][value='chat-image']").prop("checked", true);
            var image = wp.media({
                title: 'Select image for channel',
                multiple: false,
                library: {
                    type: 'image'
                }
            }).open()
                .on('select', function (e) {
                    var imageData = image.state().get('selection').first();
                    imageData = imageData.toJSON();
                    $('#widget_custom_img').val(imageData.id);
                    $("#chat-image-icon .svg-chat-icon svg").remove();
                    $("#chat-image-icon .svg-chat-icon img").remove();
                    $('#chat-image-icon .svg-chat-icon').append("<img src='"+imageData.url+"' alt='widget-custom-img' />");
                    $('#chat-image-icon .svg-chat-icon .custom-img').append("<img src='"+imageData.url+"' alt='widget-custom-img' />");
                    $('#chat-image-icon').addClass("has-image");
                    $(".csaas-widget .csaas-svg img").attr("src",imageData.url);

                });
        });

        // if($("#chat-fa-icon .icon-chat").length) {
        //     $(document).on("click", "#chat-fa-icon", function(){
        //         var newIconLib = {
        //             "material":{
        //                 "regular":{
        //                     "list-icon":"",
        //                     "icon-style":"mt-regular",
        //                     "icons":["some","some2"],
        //                 }
        //             }
        //         }
        //
        //         AestheticIconPicker({
        //             'selector': '#icon-select-chat', // must be an ID
        //             'onClick': '#chat-fa-icon',  // must be an ID
        //             "iconLibrary": newIconLib
        //         });
        //     });
        // }

        $(document).on("click", ".more-btn", function(){
            if($(".cc-bcc-field").hasClass("active")) {
                $(this).text("CC/BCC");
                $(this).append("<i class='fas fa-arrow-down up-down-arrow'></i>");
                $(".cc-bcc-field").removeClass("active");

            } else {
                $(this).text("CC/BCC");
                $(this).append("<i class='fas fa-arrow-up up-down-arrow'></i>");
                $(".cc-bcc-field").addClass("active");

            }

        });

        $(document).on("click", ".recaptcha-type", function (){
            $(".recaptcha-input").removeClass("v2-key v3-key");
            if($(this).val() == "v2") {
                $(".recaptcha-input").addClass("v2-key");
            } else {
                $(".recaptcha-input").addClass("v3-key");
            }
        });


        $(document).on("change", "input[name='cta_type']:checked", function(){
            if($(this).val() == "simple-view") {
                $("#simple-view").removeClass("hide-it");
                $("#chat-view").addClass("hide-it");
                $(".chaty-chat-view-option").removeClass("hide-option");
                $("#cta-header-color-setting").addClass("hide-it");
            } else {
                $("#simple-view").addClass("hide-it");
                $("#chat-view").removeClass("hide-it");
                $(".chaty-chat-view-option").addClass("hide-option");
                $("#cta-header-color-setting").removeClass("hide-it");
            }

            change_custom_preview();
        });

        $(document).on("click", ".add-properties", function(e){
            e.preventDefault();
            $(this).closest(".properties-box").toggleClass("active");
        });

        $(document).on("click", ".properties-list a", function(e){
            e.preventDefault();
            var inputText = $.trim($(this).data("txt"));
            if($(this).closest(".form-horizontal__item").hasClass("has-iframe")) {
                var iframeData = $(this).closest(".form-horizontal__item").find("iframe");
                if(iframeData.contents().find('p').length) {
                    iframeData.contents().find('p:last-child').append(" "+inputText);
                } else {
                    iframeData.contents().find('.mce-content-body').append(" "+inputText);
                }
            } else if($(this).closest(".custom-input-tags").length) {
                inputText = $.trim($.trim($(this).closest(".custom-input-tags").find(".add-custom-tags").val()) + " "+ "\n"+ inputText);
                $(this).closest(".custom-input-tags").find(".add-custom-tags").val(inputText);
            } else if($(this).closest(".email-body-content").length) {
                inputText = $.trim($.trim($(this).closest(".email-body-content").find(".custom-email-body").val()) + "\n"+ inputText);
                $(this).closest(".email-body-content").find(".custom-email-body").val(inputText);
            } else if($(this).closest(".form-horizontal__item").length) {
                inputText = $.trim($.trim($(this).closest(".form-horizontal__item").find(".add-properties").val()) + " "+ inputText);
                $(this).closest(".form-horizontal__item").find(".add-properties").val(inputText);
            }

            change_custom_preview();
        });

        $(".form-fonts").SumoSelect({
            placeholder: 'Select font family',
            csvDispCount: 3
        });

        $("#chaty_attention_effect, #chaty_default_state").SumoSelect({
            placeholder: 'Select font family',
            csvDispCount: 3
        });

        $(document).on("click", "#cta_woocommerce_status", function(){
            if($(this).is(":checked")) {
                $(".woocommerce-settings").addClass("active");
            } else {
                $(".woocommerce-settings").removeClass("active");
            }
        });

        // call when any channel is removed or updated
        const channel_list4 = [];
        jQuery('.channels-icons > .icon.active').each( (i, item) => {
            channel_list4.push( item.dataset.social );
        } )
        wp.hooks.doAction('chaty.channel_update', {
            channel     : channel_list4,         // active channel list
            target      : null,               // channel that removed last
            action      : 'added',            // added || removed,
            isExceeded  : false,
        }); 

        var whatsAppInput = [];



        $(document).on("change", ".chaty-close_form_after-setting", function(){
            setTimeout(function(){
                $(".chaty-close_form_after-setting").each(function(){
                    if($(this).is(":checked")) {
                        $(this).closest(".form-field-setting-col").find(".close_form_after-settings").addClass("active");
                    } else {
                        $(this).closest(".form-field-setting-col").find(".close_form_after-settings").removeClass("active");
                    }
                });
            },100);
        });

        $(document).on("change", "#save_leads_locally_Contact_Us", function(){
            if(!$(this).is(":checked")) {
                $("#custom-leads-popup").show();
            }
        });

        $(document).on("click", ".aim-insert-icon-button", function(e){
            e.preventDefault();
        });

        var newIconLib = {
            "material":{
                "regular":{
                    "list-icon":"",
                    "icon-style":"mt-regular",
                    "icons":["some","some2"],
                }
            }
        }

        if($(".icon-picker-wrap").length) {
            $(".icon-picker-wrap").each(function(){
                var dataSlug = $(this).data("slug");
                AestheticIconPicker({
                    'selector': '#icon-picker-'+dataSlug, // must be an ID
                    'onClick': '#select-icon-'+dataSlug,  // must be an ID
                    "iconLibrary": newIconLib
                });
            });
        }

        $(document).on("keyup", "#channel_input_Whatsapp", function(){
            if($.trim($(this).val()) != "") {
                var iti = cht_settings.channel_settings['Whatsapp_Country'];
                var data = iti.getSelectedCountryData();
                var value = $.trim($(this).val());
                if(value[0] != "+") {
                    value = "+"+value;
                    $(this).val(value);
                    $(this).trigger("change");
                }

                if (Reflect.has(data, 'dialCode')) {
                    var dialCode = data.dialCode;

                    if (value.length > dialCode.length + 1 && !value.includes('-') && value.startsWith(`+${dialCode}`)) {
                        var number = value.replace(`+${dialCode}`, '')
                        number = number.replaceAll(" ", "");
                        $(this).val('+' + dialCode + '-' + number);
                    }

                    if (value.length > dialCode.length + 1 && value.startsWith(`+${dialCode}-`)) {
                        var number = value.replace(`+${dialCode}-`, '')
                        number = number.replaceAll(" ", "");
                        $(this).val('+' + dialCode + '-' + number);
                    }
                }

                if(data.dialCode) {
                    if ((value.length <= data.dialCode.length + 1)) {
                        $(this).closest(".test-btn").find(".wf-test-button").removeClass("active");
                    } else {
                        $(this).closest(".test-btn").find(".wf-test-button").addClass("active");
                    }
                }

                if(value.length > 0 && !data.dialCode) {
                    $(this).closest(".test-btn").find(".wf-test-button").removeClass("active");
                }

                if(value[0] == "+" && $(this).val().length == 1) {
                    $(this).closest(".channels__input-box").find(".iti__selected-flag").find(".iti__flag").attr("class", "iti__flag");
                    $(this).closest(".channels__input-box").find(".iti__selected-flag").attr("title", "");
                }

            } else if($.trim($(this).val()) == "") {
                $(this).closest(".channels__input-box").find(".iti__selected-flag").find(".iti__flag").attr("class", "iti__flag");
                $(this).closest(".channels__input-box").find(".iti__selected-flag").attr("title", "");
            }
        });

        $(document).on("keyup", ".custom-agent-channel-Whatsapp", function(){
            if($.trim($(this).val()) != "") {
                var thisCount = $(this).data("id");
                var iti = cht_settings.channel_settings['Whatsapp_agent_Country_'+thisCount];
                var data = iti.getSelectedCountryData()
                var value = $.trim($(this).val());
                if(value[0] != "+") {
                    value = "+"+value;
                    $(this).val(value);
                    $(this).trigger("change");
                }

                if (Reflect.has(data, 'dialCode')) {
                    var dialCode = data.dialCode;
                    if (value.length > dialCode.length + 1 && !value.includes('-') && value.startsWith(`+${dialCode}`)) {
                        var number = value.replace(`+${dialCode}`, '')
                        number = number.replaceAll(" ", "");
                        $(this).val('+' + dialCode + '-' + number);
                    }

                    if (value.length > dialCode.length + 1 && value.startsWith(`+${dialCode}-`)) {
                        var number = value.replace(`+${dialCode}-`, '')
                        number = number.replaceAll(" ", "");
                        $(this).val('+' + dialCode + '-' + number);
                    }
                }

                if(data.dialCode) {
                    if ((value.length <= data.dialCode.length + 1)) {
                        $(this).closest(".agent-Whatsapp-btn").find(".wf-test-button").removeClass("active");
                    } else {
                        $(this).closest(".agent-Whatsapp-btn").find(".wf-test-button").addClass("active");
                    }
                }

                if(value.length > 0 && !data.dialCode) {
                    $(this).closest(".agent-Whatsapp-btn").find(".wf-test-button").removeClass("active");
                }

                if(value[0] == "+" && $(this).val().length == 1) {
                    $(this).closest(".agent-channel-input").find(".iti__selected-flag").find(".iti__flag").attr("class", "iti__flag");
                    $(this).closest(".agent-channel-input").find(".iti__selected-flag").attr("title", "");
                }

            } else if($.trim($(this).val()) == "") {
                $(this).closest(".agent-channel-input").find(".iti__selected-flag").find(".iti__flag").attr("class", "iti__flag");
                $(this).closest(".agent-channel-input").find(".iti__selected-flag").attr("title", "");
            }
        });

        $(document).on("change", ".chaty-redirect-setting", function(){
            setTimeout(function(){
                $(".chaty-redirect-setting").each(function(){
                    if($(this).is(":checked")) {
                        $(this).closest(".form-field-setting-col").find(".redirect_action-settings").addClass("active");
                    } else {
                        $(this).closest(".form-field-setting-col").find(".redirect_action-settings").removeClass("active");
                    }
                });
            },100);
        });

        $(document).on("click", ".email-setting", function(){
            setTimeout(function(){
                $(".email-setting-field").each(function(){
                    if($(this).is(":checked")) {
                        $(this).closest(".form-field-setting-col").find(".email-settings").addClass("active");
                    } else {
                        $(this).closest(".form-field-setting-col").find(".email-settings").removeClass("active");
                    }
                });
            },100);
        });

        $(document).on("click", ".captcha-setting-field", function(){
            if($(this).is(":checked")) {
                $(this).closest(".form-field-setting-col").find(".captcha-settings").addClass("active");
            } else {
                $(this).closest(".form-field-setting-col").find(".captcha-settings").removeClass("active");
            }
        });

        $(document).on("click", ".email-content-switch", function(e){
            if($(this).is(":checked")) {
                $(".email-body-content").addClass("active");
            } else {
                $(".email-body-content").removeClass("active");
            }
        });

        setTimeout(() => {
            $(".whatsapp-welcome-message iframe")
            .contents().find('body')
            .css({
                backgroundColor: '#fff',
                margin: 0,
                padding: '8px 10px'
            });
        }, 100)

        $(document).on("click", ".chaty-embedded-window", function(){
            setTimeout(function(){
                $(".embedded_window-checkbox").each(function(){
                    if($(this).is(":checked")) {
                        $(this).closest("li.chaty-channel").find(".whatsapp-welcome-message").addClass("active");
                        // make the welcome message input box background white
                        // issues: It used to take the default background color of the theme like twenty twenty one
                        const iframe = $(this).closest("li.chaty-channel").find(".whatsapp-welcome-message iframe");
                        iframe.contents().find('body').css({
                            backgroundColor: '#fff',
                            margin: 0,
                            padding: '8px 10px'
                        });
                        iframe.contents().find('head').append("<style>p {margin:0; padding:0}</style>");

                        var buttonHtml = $(".csaas-widget").find(".csaas-cta-close").find("button").html();
                        var dataForm = "csaas-form-0-Whatsapp";
                        $("#" + dataForm).addClass("is-active");

                        $(".csaas-widget").removeClass("csaas-open");
                        $(".csaas").addClass("form-open");
                        $("#" + dataForm).addClass("active");

                        $(".csaas-widget").find(".open-csaas-channel").html(buttonHtml);
                    } else {
                        $(this).closest("li.chaty-channel").find(".whatsapp-welcome-message").removeClass("active");

                        var dataForm = "csaas-form-0-Whatsapp";
                        $("#" + dataForm).removeClass("is-active");

                        $(".csaas-widget").addClass("csaas-open");
                        $(".csaas").removeClass("form-open");
                        $("#" + dataForm).removeClass("active");
                    }
                });
            },300);
        });

        $(document).on("change", "#cht-form input", function(){
            whatsappStatus = false;
            phoneStatus = false;
            fbStatus = false;
            smsStatus = false;
            viberStatus = false;
            phoneNumberStatus = false;
        });

        $(document).on("click", ".remove-js-script", function(e){
            e.preventDefault();
            $(this).closest(".channels__input-box").find("input").val("");
            $(this).closest(".channels__input-box").find("input").removeClass("cht-input-error").removeClass("cht-js-error");
            $(this).closest(".channels__input-box").find(".cht-error-message").remove();
            $(this).remove();
        });

        $("#cht-form").on("submit", function () {
            if(forceSubmit) {
                return true;
            }
            set_social_channel_order();
            save_contact_form_field_order();
            $("#chaty-page-options .cht-required").removeClass("cht-input-error");
            $(".chaty-data-and-time-rules .cht-required").removeClass("cht-input-error");
            $(this).find(".cht-error-message").remove();
            $(this).find(".remove-js-script").remove();
            var errorCount = 0;
            if ($("#chaty-page-options .cht-required").length) {
                $("#chaty-page-options .cht-required").each(function () {
                    if (jQuery.trim($(this).val()) == "") {
                        $(this).addClass("cht-input-error");
                        errorCount++;
                    }
                });
            }
            if ($(".chaty-data-and-time-rules .cht-required").length) {
                $(".chaty-data-and-time-rules .cht-required").each(function () {
                    if (jQuery.trim($(this).val()) == "") {
                        $(this).addClass("cht-input-error");
                        errorCount++;
                    }
                });
            }
            if (!cht_nonce_ajax.has_js_access) {
                $("#channels-selected-list .channels__input").each(function(){
                    if($.trim($(this).val()) != "") {
                        if(($.trim($(this).val()).toLowerCase()).indexOf("javascript") != -1) {
                            $("body, html").scrollTop(0);
                            $("#chaty-social-channel").trigger("click");
                            $(this).addClass("cht-input-error cht-js-error");
                            $(this).after("<span class='cht-error-message'>"+cht_nonce_ajax.js_message+"</span><a href='#' class='remove-js-script'>"+cht_nonce_ajax.remove+"</a>")
                            errorCount++;
                        }
                    }
                });
            }
            if(errorCount == 0) {
                return checkForWhatsAppNumber();
            } else {
                $(".cht-input-error:first").focus();
                return false;
            }
        });

        $(".close-chaty-popup-btn").on("click", function(e){
            e.stopPropagation();
            $(".chaty-popup").hide();
            if($(this).hasClass("keep-leads-in-db")) {
                $("#save_leads_locally_Contact_Us").prop("checked", true);
                $("#custom-leads-popup").hide();
            } else if($(this).hasClass("channel-setting-btn") || $(this).hasClass("channel-setting-step-btn")) {
                $("#chaty-social-channel, #chaty-app-customize-widget, #chaty-triger-targeting").removeClass("completed");
                $("#chaty-social-channel, #chaty-app-customize-widget, #chaty-triger-targeting").removeClass("active");
                $("#chaty-social-channel").addClass("active");
                $(".social-channel-tabs").removeClass("active");
                $("#chaty-tab-social-channel").addClass("active");
                $("body, html").animate({
                    scrollTop: $("#channel-list").offset().top - 125
                }, 250);
            } else if($(this).hasClass("select-trigger-btn")) {
                $("#chaty-triger-targeting").removeClass("completed");
                $("#chaty-social-channel, #chaty-app-customize-widget, #chaty-triger-targeting").removeClass("active");
                $("#chaty-triger-targeting").addClass("active");
                $("#chaty-social-channel, #chaty-app-customize-widget").addClass("completed");
                $(".social-channel-tabs").removeClass("active");
                $("#chaty-tab-triger-targeting").addClass("active");
                $("body, html").animate({
                    scrollTop: $("#trigger-setting").offset().top - 50
                }, 250);
            } else if($(this).hasClass("next-step-btn")) {
                $(".chaty-popup").hide();
                $("#chaty-app-customize-widget, #chaty-triger-targeting").removeClass("completed");
                $("#chaty-social-channel, #chaty-app-customize-widget, #chaty-triger-targeting").removeClass("active");
                $("#chaty-app-customize-widget").addClass("active");
                $("#chaty-social-channel").addClass("completed");
                $(".social-channel-tabs").removeClass("active");
                $("#chaty-tab-customize-widget").addClass("active");
            }
        });

        $(document).on("click", ".remove-zero", function(e){
            e.stopPropagation();
            e.preventDefault();
            let indexId = $(this).data("index");
            if($("#cht_social_agent_Whatsapp_"+indexId).length) {
                let inputVal = $("#cht_social_agent_Whatsapp_"+indexId).val();
                inputVal = inputVal.replace(/-0+/, '-');
                $("#cht_social_agent_Whatsapp_"+indexId).val(inputVal);
                $(this).closest(".number-list").remove();
            }

            if($("#whatsapp-message-popup .number-list").length > 1) {
                $("#whatsapp-message-popup").addClass("has-multiple");
            } else {
                $("#whatsapp-message-popup").removeClass("has-multiple");
            }
        });

        $(document).on("click", ".remove-zero-btn", function(){
            if(!$(".phone-number-list .number-list").hasClass("is-not-agent")) {
                let indexId = $(".phone-number-list .number-list").data("index");
                if($("#cht_social_agent_Whatsapp_"+indexId).length) {
                    let inputVal = $("#cht_social_agent_Whatsapp_"+indexId).val();
                    inputVal = inputVal.replace(/-0+/, '-');
                    $("#cht_social_agent_Whatsapp_"+indexId).val(inputVal);
                }
            } else {
                let inputVal = $("#channel_input_Whatsapp").val();
                inputVal = inputVal.replace(/-0+/, '-');
                $("#channel_input_Whatsapp").val(inputVal);
            }
            $(".chaty-popup").hide();
            isWhatsAppValidated = true;
            checkPreSettings();
        });

        $(document).on("change", ".custom-agent-channel-Whatsapp, .custom-channel-Whatsapp, #channel_input_Whatsapp", function(){
            isWhatsAppValidated = false;
        });

        $(".chaty-popup-outer").on("click", function(e){
            $(".chaty-popup").hide();
        });

        $(".check-for-numbers").on("click", function(){
            checkPreSettings();
        });

        $(".check-for-device").on("click", function(){
            checkForDevices();
        });

        $(".check-for-triggers").on("click", function(){
            checkForTriggers();
        });

        $(".fill-agent-value").on("click", function(){
            $("#channels-selected-list > li.has-agent-view .chaty-agent-list .chaty-agent-name").removeClass("empty-agent");
            $("#channels-selected-list > li.has-agent-view .chaty-agent-list .chaty-agent-name").each(function(){
                if($.trim($(this).val()) == "") {
                    $(this).addClass("empty-agent");
                }
            });
            $("#chaty-social-channel, #chaty-app-customize-widget, #chaty-triger-targeting").removeClass("active");
            $("#chaty-social-channel, #chaty-app-customize-widget, #chaty-triger-targeting").removeClass("completed");
            $("#chaty-social-channel").addClass("active");
            $(".social-channel-tabs").removeClass("active");
            $("#chaty-tab-social-channel").addClass("active");
            $("#channels-selected-list > li.has-agent-view .chaty-agent-list .chaty-agent-name.empty-agent:first").focus();
        });
        $(".check-for-status").on("click", function(){
            checkForStatus();
        });
        $(".change-status-and-save").on("click", function(){
            $(".cht_active").prop("checked", true);
            forceSubmit = true;
            $(".chaty-popup").hide();
            $("#cht-form").trigger("submit");
        });
        $(".status-and-save").on("click", function(){
            $(".cht_active").prop("checked", false);
            forceSubmit = true;
            $(".chaty-popup").hide();
            $("#cht-form").trigger("submit");
        });
        $(document).on("click", ".preview-section-chaty", function(e){
            e.stopPropagation();
        });

        // jQuery(".chaty-color-field.cht-color").trigger("change");

        $(document).on("change", ".cht-color", function(){
            setWidgetIconColor();
        });

        setWidgetIconColor();

        $(document).on("click", "#send_leads_mailchimp", function(){
            if($(this).is(":checked")) {
                $(this).closest(".form-field-setting-col").find(".mailchimp-settings").addClass("active");
            } else {
                $(this).closest(".form-field-setting-col").find(".mailchimp-settings").removeClass("active");
            }
        });

        $(document).on("click", "#send_leads_mailchimp.has-mailchimp-integration", function(){
            $(this).prop("checked", false);
            window.location = cht_settings.integration_url;
        });

        $(document).on("change", "#mailchimp-enable-tag" , function (e) {
            if($(this).prop('checked') === true ) {
                $('.chaty-mailchimp-tags-info').show();
            } else {
                $('.chaty-mailchimp-tags-info').hide();
            }
        });

        $(document).on("change", "#mailchimp-enable-group" , function (e) {
            if($(this).prop('checked') === true ) {
                $('.mailchimp-group-info').show();
            } else {
                $('.mailchimp-group-info').hide();
            }
        });

        $(document).on("change", "#chaty_mailchimp_lists" , function (e) {

            var widget_no = $("#widget_index").val();
            if (typeof widget_no == 'undefined' ) {
                widget_no = '';
            }
            $('.chaty-mailchimp-groups ').show();
            if($(".chaty-mailchimp-field-lists").find(".chaty-setting-col").length > 1) {
                $('.chaty-mailchimp-field-mapping').show();
            }
            var list_id = $(this).val();

            jQuery.ajax({
                url: ajaxurl,
                type:'post',
                data: 'action=chaty-mailchimp-group&widget_no='+widget_no+'&list_id=' + $(this).val() +'&wpnonce=' + cht_settings.ajax_nonce,
                success: function ( data ) {
                    $('.chaty-mailchimp-groups #mailchimp-group').html(data);
                },
            });

            jQuery.ajax(
                {
                    url: ajaxurl,
                    type:'post',
                    data: 'action=chaty-mailchimp-field&widget_no='+widget_no+'&list_id=' + $(this).val() +'&wpnonce=' + cht_settings.ajax_nonce,
                    success: function ( data ) {
                        data = $.parseJSON(data);
                        if (data.mapping_field != '' ) {
                            $('.chaty-mailchimp-field-mapping').show();
                            $('.chaty-mailchimp-field-lists').html(data.mapping_field);
                        } else {
                            if($(".chaty-mailchimp-field-lists").find(".chaty-setting-col").length >= 1 && list_id != "") {
                                $(".chaty-mailchimp-field-lists .chaty-setting-col").each(function (){
                                    if($(this).find("select").html() == "") {
                                        $(this).find("select").html(data.mapping_option);
                                    }
                                });
                                $('.chaty-mailchimp-field-mapping').show();
                            } else {
                                $('.chaty-mailchimp-field-mapping').hide();
                            }
                        }
                    },
                }
            );

        });

        $(document).on("click", "#send_leads_klaviyo", function(){
            if($(this).is(":checked")) {
                $(this).closest(".form-field-setting-col").find(".klaviyo-settings").addClass("active");
            } else {
                $(this).closest(".form-field-setting-col").find(".klaviyo-settings").removeClass("active");
            }
        });

        $(document).on("click", "#send_leads_klaviyo.has-klaviyo-integration", function(){
            $(this).prop("checked", false);
            window.location = cht_settings.integration_url;
        });

    });
});

function setWidgetIconColor() {
    jQuery(".svg-chat-icon.upload-icons").css("background-color", jQuery(".cht-color").val());
    jQuery(".svg-chat-icon.upload-icons i").css("color", jQuery(".cht-icon-color").val());
}

function check_for_number_chaty(phoneNumber, validationFor) {
    if (phoneNumber != "") {
        if (phoneNumber[0] == "+") {
            phoneNumber = phoneNumber.substr(1, phoneNumber.length)
        }
        if (validationFor == "Phone") {
            if (phoneNumber[0] == "*") {
                phoneNumber = phoneNumber.substr(1, phoneNumber.length)
            }
        }
        if (isNaN(phoneNumber)) {
            return true;
        }
    }
    return false;
}

(function ($) {
    var closeAction = 0;

    var InMobile = (/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|ipad|iris|kindle|Android|Silk|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i.test(navigator.userAgent) || /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(navigator.userAgent.substr(0, 4))) ? 1 : 0;

    jQuery(window).on('popstate', function(event) {
        window.onbeforeunload = null;
        if(window.history && window.history.pushState) {
            window.history.back();
        }
    });

    jQuery(document).ready(function () {
        if(!$(".chaty-table").length) {
            $('body input, body .icon, body textarea, body .btn-cancel:not(.close-btn-set) ').on("click", function (event) {
                window.onbeforeunload = function (e) {
                    e = e || window.event;
                    e.preventDefault = true;
                    e.cancelBubble = true;
                    e.returnValue = 'Your beautiful goodbye message';
                };
            });
        }

        if($(".country-list").length) {
            $(".country-list").SumoSelect({
                placeholder: "Select country",
                search: true,
                selectAll: true,
                clearAll: true
            });
        }

        $(document).on('submit', 'form', function (event) {
            window.onbeforeunload = null;
        });

        $(document).on('change', '.channel-select-input', function (event) {
            var selChannel = $(this).closest("li").attr("data-id");
            jQuery.ajax({
                type: 'POST',
                url: ajaxurl,
                dataType: 'json',
                data: {
                    social: $(this).val(),
                    channel: selChannel,
                    action: 'get_chaty_settings'
                },
                success: function (response) {
                    if(response.status == 1) {
                        if(response.data.slug == "Whatsapp") {
                            if($("#channel_input_"+response.channel).length) {
                                cht_settings.channel_settings[response.channel] = document.querySelector("#channel_input_"+response.channel);
                                window.intlTelInput(cht_settings.channel_settings[response.channel], {
                                    dropdownContainer: document.body,
                                    formatOnDisplay: true,
                                    hiddenInput: "full_number",
                                    initialCountry: "auto",
                                    nationalMode: false,
                                    utilsScript: cht_settings.plugin_url + "admin/assets/js/utils.js",
                                });
                            }
                        } else {
                            $("#chaty-social-"+response.channel+" .channels__input-box").html("<input type='text' class='channels__input' name='cht_social_"+response.channel+"[value]' id='channel_input_"+response.channel+"' />");
                        }
                        $(".custom-icon-"+response.channel+" svg").html(response.data.svg);
                        $("#chaty-social-"+response.channel).attr("data-channel", response.data.slug);
                        $("#chaty-social-"+response.channel).find(".sp-preview-inner").css("background-color", response.data.color);
                        $("#chaty-social-"+response.channel).find(".chaty-color-field").val(response.data.color);
                        $("#chaty-social-"+response.channel).find(".channels__input").attr("placeholder", response.data.placeholder);
                        $("#chaty-social-"+response.channel).find(".channel-example").text(response.data.example);
                        $("#chaty-social-"+response.channel).find(".chaty-title").val(response.data.title);
                        $("#chaty-social-"+response.channel).find(".icon").attr("data-title", response.data.title);
                        $("#chaty-social-"+response.channel).find(".chaty-color-field").trigger("change");
                        $(".help-section").html("");
                        if(response.data.help_link != "") {
                            $(".help-section").html('<div class="viber-help"><a target="_blank" href="'+response.data.help_link+'">'+response.data.help_title+'</a></div>');
                        } else if(response.data.help_text != "") {
                            $(".help-section").html('<div class="viber-help"><span class="help-text">'+response.data.help_text+'</span><span class="help-title">'+response.data.help_title+'</span></div>');
                        }
                    }
                }
            })
        });

        $(document).on("blur", "#channels-selected-list > li:not(#chaty-social-close) .channels__input", function(){
            if($(this).hasClass("border-red") && $(this).val() != "") {
                $(this).removeClass("border-red");
            }
        });

        var count_click = 1000000003;
        $('.show_up').on("click", function () {
            count_click += 10;
            $('#upgrade-modal').css({
                'z-index': count_click,
                display: 'block',
                'margin-left': '-258px'
            });
        });

        $('.color-picker-btn, .color-picker-btn-close, .color-picker-custom button').on('click', function (e) {
            e.preventDefault();

            $('.color-picker-box').toggle();
            $('.color-picker-btn').toggle();
        });

        $(document).on('change', 'input[name="cht_color"]:checked', function () {
            var $this = $(this);

            var color = $this.val();

            var title = $this.prop('title');
            $('.color-picker-btn .circle').css({backgroundColor: color});
            $('.color-picker-btn .text').text(title);
            $('#chaty-social-close ellipse').attr("fill", color);
            $('.preview .page #iconWidget svg circle').css({fill: color});
            $('.preview .page .chaty-close-icon ellipse').css({fill: color});
            $("#cht_custom_color").val($(this).val());
            $(".upload-icons").css("background-color",$(this).val());
        });

        $(document).on("click", ".color-field", function(){
            if($(this).is(":checked")) {
                $(this).spectrum("show");
            }
        });

        var socialIcon = $('.channels-icons > .icon-sm');
        var socialInputsContainer = $('.social-inputs');
        var click = 0;

        socialIcon.on('click', function () {
            ++click;

            $('#popover').removeClass("shake-it");

            var $this = $(this);

            var social = $this.data('social');

            if ($this.hasClass('active')) {
                icon = $(this).data('social');
                $("#channels-selected-list #chaty-social-"+icon).remove();
                $this.toggleClass('active');
                change_custom_preview();
                // call when any channel is removed or updated
                const channel_list3 = [];
                $('.channels-icons > .icon.active').each( (i, item) => {
                    channel_list3.push( item.dataset.social );
                } )
                wp.hooks.doAction('chaty.channel_update', {
                    channel     : channel_list3,         // active channel list
                    target      : social,               // channel that removed last
                    action      : 'removed',            // added || removed,
                    isExceeded  : false,
                });
                return;
            }
            socialIcon.addClass('disabled');
            icon = $(this).data('social');

            $this.toggleClass('active');


            if ($('section').is('#pro')) {
                var token = 'pro';
            } else {
                var token = 'free';
            }


            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: ajaxurl,
                data: {
                    action: 'choose_social',
                    social: social,
                    nonce_code: cht_nonce_ajax.cht_nonce,
                    version: token,
                    widget_index: $("#widget_index").val()
                },
                beforeSend: function (xhr) {

                },
                success: function (data) {
                    var item = $(data);
                    var itemName = item.find('.icon').data('title');
                    var itemChannel = item.data('channel');
                    socialIcon.removeClass('disabled');
                    if (!$('.channels-selected div[data-social="' + itemName + '"]').length) {
                        $('#chaty-social-close').before(item);
                        if($("#chaty-social-"+social+" .chaty-whatsapp-setting-textarea").length) {
                            editorId = $("#chaty-social-"+social+" .chaty-whatsapp-setting-textarea").attr("id");
                            tinymce.init({
                                selector: $("#chaty-social-"+social+" .chaty-whatsapp-setting-textarea").attr("id"),
                                toolbar: 'bold, italic, underline | emoji',
                                menubar: false,
                                branding: false,
                                setup: function(editor) {
                                    if(editor.id != "textblock_editor" && editor.id != "custom_textblock") {
                                        editor.addButton('emoji', {
                                            image: cht_settings.icon_img,
                                            onclick: insertEmoji,
                                            classes: 'emoji-custom-icon'
                                        });
                                    }

                                    editor.on('keyup', function (e){
                                        change_custom_preview();
                                        setWhatsAppPopupContent(editor.getContent());
                                    });
            
                                    function insertEmoji() {
                                        const { createPopup } = window.picmoPopup;
                                        const trigger = jQuery(".mce-emoji-custom-icon button").attr("id");
                                        const trig = document.querySelector("#"+trigger);
            
                                        const picker = createPopup({}, {
                                            referenceElement: trig,
                                            triggerElement: trig,
                                            position: 'right-start',
                                            hideOnEmojiSelect: false
                                        });
            
                                        picker.toggle();
            
                                        picker.addEventListener('emoji:select', (selection) => {
                                            let editor = tinyMCE.get(editorId);
                                            if(!editor.selection.getNode() || editor.selection.getNode() === editor.getBody()) {
                                                editor.focus();
                                                // Move the cursor to the end of the content
                                                editor.selection.select(editor.getBody(), true);
                                                editor.selection.collapse(false);
                                                tinymce.activeEditor.execCommand('mceInsertContent', false, selection.emoji);
                                            } else {
                                                tinymce.activeEditor.execCommand('mceInsertContent', false, selection.emoji);
                                            }
                                            // tinyMCE.get(editorId).focus();
                                            change_custom_preview();
                                            setWhatsAppPopupContent(editor.getContent());
                                        });
                                    }
                                }
                            });
                            tinymce.execCommand( 'mceAddEditor', true, editorId);
                        }
                    }

                    jQuery("#chaty-social-Contact_Us .chaty-default-settings .form-field-setting-col").sortable({
                        placeholder: "sort-contact-form-field-placeholder",
                        handle: ".sort-contact-form-field",
                        start: function() {
                        },
                        stop: function () {
                        },
                        update: function (event, ui) {
                            change_custom_preview();
                            save_contact_form_field_order();
                        }
                    });

                    var newIconLib = {
                        "material":{
                            "regular":{
                                "list-icon":"",
                                "icon-style":"mt-regular",
                                "icons":["some","some2"],
                            }
                        }
                    }
                    if($("#icon-picker-"+social).length && $("#select-icon-"+social).length) {
                        AestheticIconPicker({
                            'selector': '#icon-picker-' + social, // must be an ID
                            'onClick': '#select-icon-' + social,  // must be an ID
                            "iconLibrary": newIconLib
                        });
                    }
                    if($("#icon-picker-agent-"+social).length && $("#select-icon-agent-"+social).length) {
                        AestheticIconPicker({
                            'selector': '#icon-picker-agent-' + social, // must be an ID
                            'onClick': '#select-icon-agent-' + social,  // must be an ID
                            "iconLibrary": newIconLib
                        });
                    }
                    if($("#chaty-social-"+social+" .agent-list li.agent-info .icon-picker-wrap").length) {
                        $("#chaty-social-"+social+" .agent-list li.agent-info .icon-picker-wrap").each(function(){
                            var dataSulg = $(this).data("slug");
                            if($("#icon-picker-"+dataSulg).length && $("#select-icon-"+dataSulg).length) {
                                AestheticIconPicker({
                                    'selector': '#icon-picker-' +dataSulg, // must be an ID
                                    'onClick': '#select-icon-' +dataSulg,  // must be an ID
                                    "iconLibrary": newIconLib
                                });
                            }
                        });
                    }

                    if(itemChannel == "Whatsapp") {

                    }

                    // if(social == "Whatsapp") {
                        if($("#channel_input_Whatsapp").length) {
                            cht_settings.channel_settings['Whatsapp'] = document.querySelector("#channel_input_Whatsapp");
                            cht_settings.channel_settings['Whatsapp_Country'] = window.intlTelInput(cht_settings.channel_settings['Whatsapp'], {
                                formatOnDisplay: false,
                                hiddenInput: 'full_number',
                                initialCountry: 'auto',
                                nationalMode: false,
                                autoHideDialCode: false,
                                utilsScript: cht_settings.plugin_url + "admin/assets/js/utils.js",
                            });

                            var user_country = getUserCountry();
                            if(user_country != "-" && $("#channel_input_Whatsapp").val() == "") {
                                setTimeout(function(){
                                    cht_settings.channel_settings['Whatsapp_Country'].setCountry(user_country);
                                    $("#channel_input_Whatsapp").closest(".channels__input-box").find(".iti__selected-flag").find(".iti__flag").addClass("iti__"+user_country);
                                    $("#channel_input_Whatsapp").trigger("keyup");
                                }, 200);
                            } else if($("#channel_input_Whatsapp").val() != "") {
                                setTimeout(function(){
                                    $("#channel_input_Whatsapp").trigger("keyup");
                                }, 200);
                            }
                        }

                        if($(".custom-agent-channel-Whatsapp").length) {
                            $(".custom-agent-channel-Whatsapp").each(function (){
                                let id = $(this).data("id");
                                cht_settings.channel_settings['Whatsapp_agent_'+id] = document.querySelector("#cht_social_agent_Whatsapp_"+id);
                                cht_settings.channel_settings['Whatsapp_agent_Country_'+id] = window.intlTelInput(cht_settings.channel_settings['Whatsapp_agent_'+id], {
                                    formatOnDisplay: false,
                                    hiddenInput: 'full_number',
                                    initialCountry: 'auto',
                                    nationalMode: false,
                                    autoHideDialCode: false,
                                    utilsScript: cht_settings.plugin_url + "admin/assets/js/utils.js",
                                });

                                var user_country = getUserCountry();
                                if(user_country != "-" && $("#cht_social_agent_Whatsapp_"+id).val() == "") {
                                    setTimeout(function(){
                                        cht_settings.channel_settings['Whatsapp_agent_Country_'+id].setCountry(user_country);
                                        $(".custom-agent-channel-Whatsapp").trigger("keyup");
                                    }, 200);
                                } else if($("#cht_social_agent_Whatsapp_"+id).val() != "") {
                                    setTimeout(function(){
                                        $(".custom-agent-channel-Whatsapp").trigger("keyup");
                                    }, 200);
                                }
                            });
                        }
                    // }

                    // if($(".custom-channel-Whatsapp").length) {
                    //     $(".custom-channel-Whatsapp").each(function(){
                    //         if(!$(this).closest(".iti__flag-container").length) {
                    //             var dataChannel = $(this).closest("li.chaty-channel").data("id");
                    //             if($("#channel_input_"+dataChannel).length) {
                    //                 cht_settings.channel_settings[dataChannel] = document.querySelector("#channel_input_" + dataChannel);
                    //                 window.intlTelInput(cht_settings.channel_settings[dataChannel], {
                    //                     dropdownContainer: document.body,
                    //                     formatOnDisplay: true,
                    //                     hiddenInput: "full_number",
                    //                     initialCountry: "auto",
                    //                     nationalMode: false,
                    //                     utilsScript: cht_settings.plugin_url + "admin/assets/js/utils.js",
                    //                 });
                    //             }
                    //         }
                    //     });
                    // }

                    change_custom_preview();

                    $(document).trigger('chatyColorPicker/trigger', [{
                        $scope   : $(`#chaty-social-${social}`),
                        element  : '.chaty-color-field'
                    }]);

                    // call when any channel is removed or updated
                    const channel_list = [];
                    $('.channels-icons > .icon.active').each( (i, item) => {
                        channel_list.push( item.dataset.social );
                    } )
                    wp.hooks.doAction('chaty.channel_update', {
                        channel     : channel_list,         // active channel list
                        target      : social,              // channel that removed last
                        action      : 'added',            // added || removed,
                        isExceeded  : false,
                    });

                },
                error: function (xhr, status, error) {

                }
            });
        });

        $('.btn-help').on("click", function (event) {
            window.open(
                'https://premio.io/help/chaty/',
                '_blank' // <- This is what makes it open in a new window.
            );
        });

        if($("#cht_color_custom").length) {
            $("#cht_color_custom").spectrum({
                chooseText: "Submit",
                preferredFormat: "hex3",
                cancelText: "Cancel",
                showInput: true,
                showAlpha: true,
                move: function (color) {
                    $(this).val(color.toRgbString());
                    $('#chaty-social-close ellipse').attr("fill", color.toRgbString());
                    $('.preview .page #iconWidget svg circle, .chaty-close-icon svg ellipse').css({fill: color.toRgbString()});
                    $("#cht_custom_color").val(color.toRgbString());
                    // $('.color-picker-btn .circle').css({background: color.toRgbString()});
                },
                change: function (color) {
                    $(this).val(color.toRgbString());
                    $('#chaty-social-close ellipse').attr("fill", color.toRgbString());
                    $('.preview .page #iconWidget svg circle, .chaty-close-icon svg ellipse').css({fill: color.toRgbString()});
                    $("#cht_custom_color").val(color.toRgbString());
                    // $('.color-picker-btn .circle').css({background: color.toRgbString()});
                }
            });
        }

        if($("#analytics_date").length) {
            $("#analytics_date").datepicker();
        }

        if($(".select2-box").length) {
            $("#cht_date_rules_time_zone").SumoSelect({
                search: true,
            });
        }

        if($("#chaty-page-options .page-option-list").length) {
            $("#chaty-page-options .page-option-list").SumoSelect({
                search: false,
            });
        }

        if($("#chaty-page-options .url-option-list").length) {
            $("#chaty-page-options .url-option-list").SumoSelect({
                search: false,
            });
        }

        if($("#chaty-page-options .pages-options").length) {
            $("#chaty-page-options .pages-options").each(function(){
                var eleId = $(this).attr("id");
                setPagesField(eleId, "pages", "Select Pages");
            });
        }

        if($("#chaty-page-options .posts-options").length) {
            $("#chaty-page-options .posts-options").each(function(){
                var eleId = $(this).attr("id");
                setPagesField(eleId, "posts", "Select Posts");
            });
        }

        if($("#chaty-page-options .wp_categories-options").length) {
            $("#chaty-page-options .wp_categories-options").each(function(){
                var eleId = $(this).attr("id");
                setPagesField(eleId, "categories", "Select Categories");
            });
        }

        if($("#chaty-page-options .wp_tags-options").length) {
            $("#chaty-page-options .wp_tags-options").each(function(){
                var eleId = $(this).attr("id");
                setPagesField(eleId, "tags", "Select Tags");
            });
        }

        if($("#chaty-page-options .wc_products-options").length) {
            $("#chaty-page-options .wc_products-options").each(function(){
                var eleId = $(this).attr("id");
                setPagesField(eleId, "products", "Select Products");
            });
        }

        if($("#chaty-page-options .wc_products_on_sale-options").length) {
            $("#chaty-page-options .wc_products_on_sale-options").each(function(){
                var eleId = $(this).attr("id");
                setPagesField(eleId, "sale_products", "Select Products");
            });
        }

        $(document).on("click", ".remove-chaty-options", function (e) {
            e.preventDefault();
            e.stopPropagation();
            if(confirm("Are you sure you want to delete this widget?")) {
                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'remove_chaty_widget',
                        widget_nonce: $(this).data("nonce"),
                        widget_index: $("#widget_index").val()
                    },
                    beforeSend: function (xhr) {

                    },
                    success: function (res) {
                        window.location = res;
                    },
                    error: function (xhr, status, error) {

                    }
                });
            }
        });

        /* Date: 2019-07-26 */
        var location_href = window.location.href;
        if (window.location.href.indexOf('page=chaty-app&widget=') > -1) {
            $('#toplevel_page_chaty-app .wp-submenu.wp-submenu-wrap li').each(function () {
                var element_href = $(this).find('a').attr('href');
                if (typeof element_href !== 'undefined') {
                    $(this).removeClass('current');
                    if (window.location.href.indexOf(element_href) > -1 && element_href.indexOf('&widget=') > -1) {
                        $(this).addClass('current');
                    }
                }
            });
        }

        jQuery(document).on("click", ".wf-test-button", function (){
            var slug = jQuery(this).data('slug');
            jQuery("#"+slug+"_popup").addClass("active");
            var link = "";
            var value = "";

            if($(this).closest(".test-btn").hasClass("agent-"+slug+"-btn")) {
                value = $(this).closest(".agent-"+slug+"-btn").find("input").val()
            } else {
                value = $(".custom-channel-"+slug).val();
            }

            if(slug == 'Whatsapp') {
                var val = value.replaceAll("+", "").replaceAll(" ", "").replaceAll("-", "");
                if(InMobile) {
                    link = "https://wa.me/" + val;
                } else {
                    link = "https://web.whatsapp.com/send?phone=" + val;
                }
                $("#"+slug+"_popup").find(".phone-value").text(value);
            }

            if(slug == "Facebook_Messenger") {
                link = value;
                $("#"+slug+"_popup").find(".phone-value").text(value);
            }

            window.open(link, '_blank');
        });

        $(document).on("click", ".edit-number", function (){
            var slug = $(this).closest(".test-popup").data("label");
            $(".custom-channel-"+slug).focus();
            $(".agent-"+slug+"-btn input").focus();
            $(".test-popup").removeClass("active");
        });

        jQuery(document).on("click", ".test-popup-bg, .test-popup-close-btn, .test-popup .save-btn", function (){
            jQuery(".test-popup").removeClass("active");
        });

        $(document).on("keyup", ".agent-Whatsapp-btn .agent-input-value, .agent-Facebook_Messenger-btn .agent-input-value, .custom-channel-Whatsapp, .custom-channel-Facebook_Messenger", function (){
            if($(this).val() != "") {
                $(this).closest(".test-btn").find(".wf-test-button").addClass("active");
            } else {
                $(this).closest(".test-btn").find(".wf-test-button").removeClass("active");
            }
        });

        $(document).on("click", ".test-popup .contact-link", function (){
           $(".chaty-help-form").addClass("active");
           $(".test-popup").removeClass("active");
        });

        $(document).on("click", "#wp_pre_set_emoji", function (){
            // alert($(".pre-set-message-whatsapp").attr("value"));
            const { createPopup } = window.picmoPopup;
            const trigger = document.querySelector("#cht_social_message_Whatsapp_pre_set_message");

            const picker = createPopup({}, {
                referenceElement: trigger,
                triggerElement: trigger,
                position: 'right-start',
                hideOnEmojiSelect: false
            });

            picker.toggle();

            picker.addEventListener('emoji:select', (selection) => {
                $("#cht_social_message_Whatsapp_pre_set_message").val($("#cht_social_message_Whatsapp_pre_set_message").val() + selection.emoji);
            });

        });

        $(document).on("click", ".wp-pre-set-emoji-agent", function (){
            // alert($(".pre-set-message-whatsapp").attr("value"));
            var agent_id = $(this).closest(".agent-channel-setting").data("item");
            const { createPopup } = window.picmoPopup;
            const trigger = document.querySelector("#cht_social_message_Whatsapp_pre_set_message_agent"+agent_id);

            const picker = createPopup({}, {
                referenceElement: trigger,
                triggerElement: trigger,
                position: 'right-start',
                hideOnEmojiSelect: false
            });

            picker.toggle();

            picker.addEventListener('emoji:select', (selection) => {
                $("#cht_social_message_Whatsapp_pre_set_message_agent"+agent_id).val($("#cht_social_message_Whatsapp_pre_set_message_agent"+agent_id).val() + selection.emoji);
            });

        });


        var customfields = $('#chaty-custom-fields-length').val();
        var custom_field = ( typeof customfields !== 'undefined') ? customfields : 1;
        $(document).on("click", '.custom-field-setting a.add-custom-field' , function (e) {
            e.preventDefault();

            var length = $('.chaty-custom-field').length;

            if (length >= 6) {
                $('.chaty-default-settings .form-field-setting-col .chaty-custom-field-limit').remove();
                var field = "<div class='chaty-custom-field-limit'><div class='social-channel-popover'><p class='description'>You can add up to 6 custom fields</p></div></div>";
                $('.chaty-default-settings .form-field-setting-col').append(field);
                $('.chaty-custom-field-limit .social-channel-popover').show();
            } else {
                var is_active = $(this).data("isactive");
                var active_url_page = $(this).data("active-page-url");

                var  free_version_div = ( is_active == 0 ) ? 'chaty-free-version' : '';

                var upgrad_div = '<div class="upgrade-chaty-link"><a href="'+active_url_page+'" target="_blank"><i class="fas fa-lock"></i>ACTIVATE YOUR KEY</a></div>';

                if(is_active == 1 ) {
                    upgrad_div='';
                }

                var field = "";
                field += "<div class='chaty-popup contact-form-field-open contact-form-setting-popup-open' id='contact_form_field_open"+custom_field+"'>";
                field += '<div class="chaty-popup-outer"></div>';
                field += '<div class="chaty-popup-inner popup-pos-bottom">';
                field += '<div class="chaty-popup-content">';
                field += '<div class="chaty-popup-close">';
                field += '<a href="javascript:void(0)" class="close-chaty-popup-btn right-2 top-2 relative">';
                field += '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path d="M15.6 15.5c-.53.53-1.38.53-1.91 0L8.05 9.87 2.31 15.6c-.53.53-1.38.53-1.91 0s-.53-1.38 0-1.9l5.65-5.64L.4 2.4C-.13 1.87-.13 1.02.4.49s1.38-.53 1.91 0l5.64 5.63L13.69.39c.53-.53 1.38-.53 1.91 0s.53 1.38 0 1.91L9.94 7.94l5.66 5.65c.52.53.52 1.38 0 1.91z"/></svg>';
                field += '</a>';
                field += '</div>';
                field += '<div class="a-card a-card--normal">';
                field += '<div class="chaty-popup-header text-left font-primary text-cht-gray-150 font-medium p-5 relative">Add new field</div>';
                field += '<div class="chaty-popup-body text-cht-gray-150 text-base px-5 py-6">';
                field += "<div class='contact-form-field-select-wrap "+free_version_div+"'><label class='contact-form-field-select'><input type='radio' value='text' data-field='"+custom_field+"' name='contact-form[custom_fields]["+custom_field+"][field_dropdown]' /><span><i class='fas fa-edit'></i>Text</span></label><label class='contact-form-field-select'><input type='radio' value='textarea' data-field='"+custom_field+"' name='contact-form[custom_fields]["+custom_field+"][field_dropdown]' /><span><i class='fas fa-align-justify'></i>Text Area</span></label><label class='contact-form-field-select'><input type='radio' value='number' data-field='"+custom_field+"' name='contact-form[custom_fields]["+custom_field+"][field_dropdown]' /><span><i class='fas fa-phone'></i>Number</span></label><label class='contact-form-field-select'><input type='radio' value='date' data-field='"+custom_field+"' name='contact-form[custom_fields]["+custom_field+"][field_dropdown]' /><span><i class='fas fa-calendar-week'></i>Date</span></label><label class='contact-form-field-select'><input type='radio' value='url' data-field='"+custom_field+"' name='contact-form[custom_fields]["+custom_field+"][field_dropdown]' /><span><i class='fas fa-link'></i>Website</span></label><label class='contact-form-field-select'><input type='radio' value='dropdown' data-field='"+custom_field+"' name='contact-form[custom_fields]["+custom_field+"][field_dropdown]' /><span><i class='fas fa-caret-down'></i>Dropdown</span>	</label><label class='contact-form-field-select'><input type='radio' value='file' data-field='"+custom_field+"' name='contact-form[custom_fields]["+custom_field+"][field_dropdown]' /><span><i class='fas fa-file-upload'></i>File Upload</span>	</label><label class='contact-form-field-select contact-form-field-textblock' style='display: none'><input type='radio' value='textblock' data-field='"+custom_field+"' name='contact-form[custom_fields]["+custom_field+"][field_dropdown]' /><span><i class='far fa-newspaper'></i>Text Block</span></label>"+upgrad_div+"</div></div><div id='contact_form_custom_dropdown"+custom_field+"' class='contact-form-dropdown-main' style='display: none;'><input type='text' name='contact-form[custom_fields]["+custom_field+"][dropdown-placeholder]' class='contact-form-dropdown-select' value='' placeholder='Select...'/><div class='contact-form-dropdown-option'><div class='option-value-field'><span class='move-icon'></span><input type='text' name='contact-form[custom_fields]["+custom_field+"][dropdown-option][]' value=''/><span class='add-customfield-dropdown-option' data-field='"+custom_field+"'>Add</span></div></div><input type='submit' name='submit' class='button button-primary' value='Save'></div><span class='contact-form-dropdfown-close'><i class='fas fa-times'></i></span>";
                field += '</div>';
                field += '</div>';
                field += '</div>';
                field += '</div>';
                field += '</div>';

                // var field = "<div id='contact_form_field_open"+custom_field+"' class='contact-form-field-open contact-form-setting-popup-open'><div class='contact-form-popup-label'><h3>Add A New Field</h3><div class='contact-form-field-select-wrap "+free_version_div+"'><label class='contact-form-field-select'><input type='radio' value='text' data-field='"+custom_field+"' name='contact-form[custom_fields]["+custom_field+"][field_dropdown]' /><span><i class='fas fa-edit'></i>Text</span></label><label class='contact-form-field-select'><input type='radio' value='textarea' data-field='"+custom_field+"' name='contact-form[custom_fields]["+custom_field+"][field_dropdown]' /><span><i class='fas fa-align-justify'></i>Text Area</span></label><label class='contact-form-field-select'><input type='radio' value='number' data-field='"+custom_field+"' name='contact-form[custom_fields]["+custom_field+"][field_dropdown]' /><span><i class='fas fa-phone'></i>Number</span></label><label class='contact-form-field-select'><input type='radio' value='date' data-field='"+custom_field+"' name='contact-form[custom_fields]["+custom_field+"][field_dropdown]' /><span><i class='fas fa-calendar-week'></i>Date</span></label><label class='contact-form-field-select'><input type='radio' value='url' data-field='"+custom_field+"' name='contact-form[custom_fields]["+custom_field+"][field_dropdown]' /><span><i class='fas fa-link'></i>Website</span></label><label class='contact-form-field-select'><input type='radio' value='dropdown' data-field='"+custom_field+"' name='contact-form[custom_fields]["+custom_field+"][field_dropdown]' /><span><i class='fas fa-caret-down'></i>Dropdown</span>	</label><label class='contact-form-field-select'><input type='radio' value='file' data-field='"+custom_field+"' name='contact-form[custom_fields]["+custom_field+"][field_dropdown]' /><span><i class='fas fa-file-upload'></i>File Upload</span>	</label><label class='contact-form-field-select contact-form-field-textblock' style='display: none'><input type='radio' value='textblock' data-field='"+custom_field+"' name='contact-form[custom_fields]["+custom_field+"][field_dropdown]' /><span><i class='far fa-newspaper'></i>Text Block</span></label>"+upgrad_div+"</div></div><div id='contact_form_custom_dropdown"+custom_field+"' class='contact-form-dropdown-main' style='display: none;'><input type='text' name='contact-form[custom_fields]["+custom_field+"][dropdown-placeholder]' class='contact-form-dropdown-select' value='' placeholder='Select...'/><div class='contact-form-dropdown-option'><div class='option-value-field'><span class='move-icon'></span><input type='text' name='contact-form[custom_fields]["+custom_field+"][dropdown-option][]' value=''/><span class='add-customfield-dropdown-option' data-field='"+custom_field+"'>Add</span></div></div><input type='submit' name='submit' class='button button-primary' value='Save'></div><span class='contact-form-dropdfown-close'><i class='fas fa-times'></i></span></div>";

                $('.chaty-contact-form-field-option').append(field);
                $("#contact_form_field_open"+custom_field).show();
                $('body').addClass('contact-form-popup-open');

                if(jQuery('.chaty-custom-field').find('.mce-tinymce').length ) {
                    jQuery('.contact-form-field-textblock').hide();
                } else {
                    jQuery('.contact-form-field-textblock').show();
                }

                custom_field++;
            }
        });

        if(jQuery('.chaty-custom-field').find('.mce-tinymce').length ) {
            jQuery('.contact-form-field-textblock').hide();
        } else {
            jQuery('.contact-form-field-textblock').show();
        }

        /* Add Dropdown Option */
        $(document).on("click", '.add-customfield-dropdown-option' , function (e) {
            var dropdown_custom_field = $(this).data('field');
            $(this).closest('.contact-form-dropdown-option').append('<div class="option-value-field ui-sortable-handle"><div class="move-icon"></div><input type="text" data-id="'+dropdown_custom_field+'" name="cht_social_Contact_Us[custom_fields]['+dropdown_custom_field+'][dropdown_option][]" value=""/><span class="delete-dropdown-option"><i class="fas fa-times"></i></span></div>');
            setDropDownRemoveFn();
        });

        $(".add-customfield-dropdown-option").on("click", function (){
            var dropdown_custom_field = $(this).data('field');
            $(this).closest('.contact-form-dropdown-option').append('<div class="option-value-field ui-sortable-handle"><div class="move-icon"></div><input type="text" data-id="'+dropdown_custom_field+'" name="cht_social_Contact_Us[custom_fields]['+dropdown_custom_field+'][dropdown_option][]" value=""/><span class="delete-dropdown-option"><i class="fas fa-times"></i></span></div>');
            setDropDownRemoveFn();
        });

        /* Open Contact form Field Option popup */
        $(document).on("click", '.contact-form-field-popup' , function (e) {
            $('#' + $(this).data('id')).show();
            $('body').addClass('contact-form-popup-open');
        });

        $(document).on("click", '.contact-form-field-open .contact-form-dropdfown-close' , function (e) {
            $('.contact-form-field-open').hide();
            $('body').removeClass('contact-form-popup-open');
        });

        $(document).on("click", '.chaty-contact-form-field-option .contact-form-field-open .contact-form-dropdfown-close' , function (e) {
            $('.chaty-contact-form-field-option .contact-form-field-open').remove();
        });

        $(document).on('click','.contact-form-dropdown-popup' ,function () {
            $(this).closest('.field-setting-col').find('.contact-form-dropdown-open').css('display','block');
            $('body').addClass('contact-form-popup-open');
        });

        $(document).on('click', '.contact-form-dropdown-open .contact-form-dropdfown-close',function () {
            $('.contact-form-dropdown-open').hide();
            $('body').removeClass('contact-form-popup-open');
        });

        setDropDownRemoveFn();

        jQuery(document).on('click','.btn-dropdown-save',function (e) {
            var option_lists = [];
            var dropdown_id = 0;
            $(this).closest(".contact-form-dropdown-open").find(".option-value-field input").each(function (){
                dropdown_id = $(this).data("id");
                option_lists.push($(this).val())
            });
            var dropdown_default = $(this).closest(".contact-form-dropdown-open").find(".contact-form-dropdown-select").val();
            $("select[name='cht_social_Contact_Us[custom_fields][" + dropdown_id + "][placeholder]']").html("");
            if(dropdown_default) {
                $("select[name='cht_social_Contact_Us[custom_fields][" + dropdown_id + "][placeholder]']").append("<option>" + dropdown_default + "</option>");
            }
            $(option_lists).each(function (key, value) {
                if(value) {
                    $("select[name='cht_social_Contact_Us[custom_fields][" + dropdown_id + "][placeholder]']").append("<option>" + value + "</option>");
                }
            });
            $('.contact-form-dropdown-open').hide();
            $('.contact-form-field-open.contact-form-setting-popup-open').hide();
            $('body').removeClass('contact-form-popup-open');
            change_custom_preview();
        });

        $('.btn-dropdown-save').on("click", function (){
            var option_lists_a = [];
            var dropdown_id_a = 0;
            $(this).closest(".contact-form-dropdown-open").find(".option-value-field input").each(function (){
                dropdown_id_a = $(this).data("id");
                option_lists_a.push($(this).val())
            });
            var dropdown_default_a = $(this).closest(".contact-form-dropdown-open").find(".contact-form-dropdown-select").val();
            $("select[name='cht_social_Contact_Us[custom_fields][" + dropdown_id_a + "][placeholder]']").html("");
            if(dropdown_default_a) {
                $("select[name='cht_social_Contact_Us[custom_fields][" + dropdown_id_a + "][placeholder]']").append("<option>" + dropdown_default_a + "</option>");
            }
            $(option_lists_a).each(function (key, value) {
                if(value) {
                    $("select[name='cht_social_Contact_Us[custom_fields][" + dropdown_id_a + "][placeholder]']").append("<option>" + value + "</option>");
                }
            });
            $('.contact-form-dropdown-open').hide();
            $('.contact-form-field-open.contact-form-setting-popup-open').hide();
            $('body').removeClass('contact-form-popup-open');
            change_custom_preview();
        });

        $(document).on(
            'mouseup', function ( event ) {
                if (!$(event.target).closest(".chaty-contact-form-field-option .contact-form-field-open, .contact-form-field-popup,.contact-form-dropdown-popup, .contact-form-dropdown-open, .contact-form-setting-popup-open").length ) {
                    $('.contact-form-field-open').hide();
                    $('.contact-form-dropdown-open').hide();
                    $('body').removeClass('contact-form-popup-open');
                }
            }
        );

        $(document).on("change", '.contact-form-field-select input[type="radio"]' , function (e) {

                var contact_form_field_select = $(this).val();
                var contact_form_field_data = $(this).data('field');
                var grid_col = "";
                var fleld_flex = "";
                if(contact_form_field_select == "textblock") {
                    fleld_flex = "has-field-flex";
                } else {
                    grid_col = "grid-cols-2";
                }

                var append_field = "";
                // append_field += '<div class="chaty-separator mt-2.5"></div>';
                append_field += '<div class="field-setting-col mt-2.5 chaty-custom-field custom-'+contact_form_field_select+'" data-order="Custom Text">';
                append_field += '<input type="hidden" name="cht_social_Contact_Us[custom_fields]['+contact_form_field_data+'][is_active]" value="no">';
                append_field += '<input type="hidden" name="cht_social_Contact_Us[custom_fields]['+contact_form_field_data+'][is_required]" value="no">';
                append_field += '<input type="hidden" name="cht_social_Contact_Us[custom_fields]['+contact_form_field_data+'][field_dropdown]" value="'+contact_form_field_select+'">';
                append_field += '<input type="hidden" name="cht_social_Contact_Us[custom_fields]['+contact_form_field_data+'][unique_id]" value="">';
                append_field += '<div class="label-flex mb-4">';
                append_field += '<label class="chaty-switch chaty-switch-toggle text-cht-gray-150 text-base" for="field_for_Contact_Us_'+contact_form_field_data+'">';
                append_field += '<input type="checkbox" class="chaty-field-setting" name="cht_social_Contact_Us[custom_fields]['+contact_form_field_data+'][is_active]" id="field_for_Contact_Us_'+contact_form_field_data+'" value="yes" checked>';
                append_field += '<div class="chaty-slider round"></div>';
                append_field += '<span class="field-label"><span id="custom_field_label'+contact_form_field_data+'">Custom Text</span>';
                append_field += '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"> <g clip-path="url(#clip0_8719_30645)"> <path d="M7.33398 2.66699H2.66732C2.3137 2.66699 1.97456 2.80747 1.72451 3.05752C1.47446 3.30756 1.33398 3.6467 1.33398 4.00033V13.3337C1.33398 13.6873 1.47446 14.0264 1.72451 14.2765C1.97456 14.5265 2.3137 14.667 2.66732 14.667H12.0006C12.3543 14.667 12.6934 14.5265 12.9435 14.2765C13.1935 14.0264 13.334 13.6873 13.334 13.3337V8.66699" stroke="#C6D7E3" stroke-width="1.33" stroke-linecap="round" stroke-linejoin="round"/> <path d="M12.334 1.66617C12.5992 1.40095 12.9589 1.25195 13.334 1.25195C13.7091 1.25195 14.0688 1.40095 14.334 1.66617C14.5992 1.93138 14.7482 2.29109 14.7482 2.66617C14.7482 3.04124 14.5992 3.40095 14.334 3.66617L8.00065 9.9995L5.33398 10.6662L6.00065 7.9995L12.334 1.66617Z" stroke="#C6D7E3" stroke-width="1.33" stroke-linecap="round" stroke-linejoin="round"/> </g> <defs> <clipPath id="clip0_8719_30645"> <rect width="16" height="16" fill="white"/> </clipPath> </defs> </svg>';
                append_field += '</span>';
                append_field += '</label>';
                append_field += '<div class="label-input">';
                append_field += '<input type="text" class="label-input-field chaty-input-text contact-form-field-text" value="Custom Text" name="cht_social_Contact_Us[custom_fields]['+contact_form_field_data+'][field_label]" />';
                append_field += '</div>';
                append_field += '<div class="sort-contact-form-field"><img src="'+ cht_settings.move_icon_img +'" alt="Move Icon"/></div>';
                append_field += '</div>';
                append_field += '<div class="field-settings active">';
                append_field += '<div class="chaty-setting-col sm:grid '+grid_col+' '+fleld_flex+' items-center gap-3">';
                append_field += '<div class="flex-first">';
                append_field += '<input class="rounded-lg w-full chaty-input-text" id="placeholder_for_Contact_Us_'+contact_form_field_data+'" type="text" name="cht_social_Contact_Us[custom_fields]['+contact_form_field_data+'][placeholder]" value="Enter Text" >';
                append_field += '</div>';
                append_field += '<div class="flex items-center space-x-3 flex-second">';
                if(contact_form_field_select != "textblock") {
                    append_field += '<div class="checkbox">';
                    append_field += '<label for="field_required_for_Contact_Us_' + contact_form_field_data + '" class="chaty-checkbox text-cht-gray-150 text-base flex items-center">';
                    append_field += '<input class="sr-only" type="checkbox" id="field_required_for_Contact_Us_' + contact_form_field_data + '" name="cht_social_Contact_Us[custom_fields][' + contact_form_field_data + '][is_required]" value="yes" checked />';
                    append_field += '<span class="mr-2"></span>';
                    append_field += 'Required';
                    append_field += '</label>';
                    append_field += '</div>';
                }
                append_field += '<div id="setting_label'+contact_form_field_data+'" class="dropdown-setting-label" style="display: none;">';
                append_field += '<a class="flex items-center space-x-1.5 contact-form-dropdown-popup" href="javascript:;">';
                append_field += '<span><svg width="16" height="16" viewBox="0 0 16 16" fill="none"> <path d="M8 10C9.10457 10 10 9.10457 10 8C10 6.89543 9.10457 6 8 6C6.89543 6 6 6.89543 6 8C6 9.10457 6.89543 10 8 10Z" stroke="currentColor" stroke-width="1.33" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M12.9332 9.99984C12.8444 10.2009 12.818 10.424 12.8572 10.6402C12.8964 10.8565 12.9995 11.056 13.1532 11.2132L13.1932 11.2532C13.3171 11.377 13.4155 11.5241 13.4826 11.6859C13.5497 11.8478 13.5842 12.0213 13.5842 12.1965C13.5842 12.3717 13.5497 12.5452 13.4826 12.7071C13.4155 12.869 13.3171 13.016 13.1932 13.1398C13.0693 13.2638 12.9223 13.3621 12.7604 13.4292C12.5986 13.4963 12.4251 13.5309 12.2498 13.5309C12.0746 13.5309 11.9011 13.4963 11.7392 13.4292C11.5774 13.3621 11.4303 13.2638 11.3065 13.1398L11.2665 13.0998C11.1094 12.9461 10.9098 12.843 10.6936 12.8038C10.4773 12.7646 10.2542 12.7911 10.0532 12.8798C9.85599 12.9643 9.68782 13.1047 9.56937 13.2835C9.45092 13.4624 9.38736 13.672 9.3865 13.8865V13.9998C9.3865 14.3535 9.24603 14.6926 8.99598 14.9426C8.74593 15.1927 8.40679 15.3332 8.05317 15.3332C7.69955 15.3332 7.36041 15.1927 7.11036 14.9426C6.86031 14.6926 6.71984 14.3535 6.71984 13.9998V13.9398C6.71467 13.7192 6.64325 13.5052 6.51484 13.3256C6.38644 13.1461 6.20699 13.0094 5.99984 12.9332C5.79876 12.8444 5.57571 12.818 5.35944 12.8572C5.14318 12.8964 4.94362 12.9995 4.7865 13.1532L4.7465 13.1932C4.62267 13.3171 4.47562 13.4155 4.31376 13.4826C4.15189 13.5497 3.97839 13.5842 3.80317 13.5842C3.62795 13.5842 3.45445 13.5497 3.29258 13.4826C3.13072 13.4155 2.98367 13.3171 2.85984 13.1932C2.73587 13.0693 2.63752 12.9223 2.57042 12.7604C2.50332 12.5986 2.46879 12.4251 2.46879 12.2498C2.46879 12.0746 2.50332 11.9011 2.57042 11.7392C2.63752 11.5774 2.73587 11.4303 2.85984 11.3065L2.89984 11.2665C3.05353 11.1094 3.15663 10.9098 3.19584 10.6936C3.23505 10.4773 3.20858 10.2542 3.11984 10.0532C3.03533 9.85599 2.89501 9.68782 2.71615 9.56937C2.53729 9.45092 2.32769 9.38736 2.11317 9.3865H1.99984C1.64622 9.3865 1.30708 9.24603 1.05703 8.99598C0.80698 8.74593 0.666504 8.40679 0.666504 8.05317C0.666504 7.69955 0.80698 7.36041 1.05703 7.11036C1.30708 6.86031 1.64622 6.71984 1.99984 6.71984H2.05984C2.2805 6.71467 2.49451 6.64325 2.67404 6.51484C2.85357 6.38644 2.99031 6.20699 3.0665 5.99984C3.15525 5.79876 3.18172 5.57571 3.14251 5.35944C3.10329 5.14318 3.00019 4.94362 2.8465 4.7865L2.8065 4.7465C2.68254 4.62267 2.58419 4.47562 2.51709 4.31376C2.44999 4.15189 2.41545 3.97839 2.41545 3.80317C2.41545 3.62795 2.44999 3.45445 2.51709 3.29258C2.58419 3.13072 2.68254 2.98367 2.8065 2.85984C2.93033 2.73587 3.07739 2.63752 3.23925 2.57042C3.40111 2.50332 3.57462 2.46879 3.74984 2.46879C3.92506 2.46879 4.09856 2.50332 4.26042 2.57042C4.42229 2.63752 4.56934 2.73587 4.69317 2.85984L4.73317 2.89984C4.89029 3.05353 5.08985 3.15663 5.30611 3.19584C5.52237 3.23505 5.74543 3.20858 5.9465 3.11984H5.99984C6.19702 3.03533 6.36518 2.89501 6.48363 2.71615C6.60208 2.53729 6.66565 2.32769 6.6665 2.11317V1.99984C6.6665 1.64622 6.80698 1.30708 7.05703 1.05703C7.30708 0.80698 7.64621 0.666504 7.99984 0.666504C8.35346 0.666504 8.6926 0.80698 8.94264 1.05703C9.19269 1.30708 9.33317 1.64622 9.33317 1.99984V2.05984C9.33402 2.27436 9.39759 2.48395 9.51604 2.66281C9.63449 2.84167 9.80266 2.98199 9.99984 3.0665C10.2009 3.15525 10.424 3.18172 10.6402 3.14251C10.8565 3.10329 11.056 3.00019 11.2132 2.8465L11.2532 2.8065C11.377 2.68254 11.5241 2.58419 11.6859 2.51709C11.8478 2.44999 12.0213 2.41545 12.1965 2.41545C12.3717 2.41545 12.5452 2.44999 12.7071 2.51709C12.869 2.58419 13.016 2.68254 13.1398 2.8065C13.2638 2.93033 13.3621 3.07739 13.4292 3.23925C13.4963 3.40111 13.5309 3.57462 13.5309 3.74984C13.5309 3.92506 13.4963 4.09856 13.4292 4.26042C13.3621 4.42229 13.2638 4.56934 13.1398 4.69317L13.0998 4.73317C12.9461 4.89029 12.843 5.08985 12.8038 5.30611C12.7646 5.52237 12.7911 5.74543 12.8798 5.9465V5.99984C12.9643 6.19702 13.1047 6.36518 13.2835 6.48363C13.4624 6.60208 13.672 6.66565 13.8865 6.6665H13.9998C14.3535 6.6665 14.6926 6.80698 14.9426 7.05703C15.1927 7.30708 15.3332 7.64621 15.3332 7.99984C15.3332 8.35346 15.1927 8.6926 14.9426 8.94264C14.6926 9.19269 14.3535 9.33317 13.9998 9.33317H13.9398C13.7253 9.33402 13.5157 9.39759 13.3369 9.51604C13.158 9.63449 13.0177 9.80266 12.9332 9.99984V9.99984Z" stroke="currentColor" stroke-width="1.33" stroke-linecap="round" stroke-linejoin="round"></path> </svg></span>';
                append_field += '<span>Settings</span>';
                append_field += '</a>';
                append_field += '</div>';
                append_field += '<div class="custom-field-remove">';
                append_field += '<span class="custom-stickyelement-delete" delete-id="'+contact_form_field_data+'">';
                append_field += '<i class="fas fa-trash-alt stickyelement-delete"></i>';
                append_field += '</span>';
                append_field += '</div>';
                append_field += '</div>';
                append_field += '</div>';
                append_field += '</div>';
                append_field += '<div class="chaty-popup contact-form-dropdown-open contact-form-setting-popup-open">';
                append_field += '<div class="chaty-popup-outer"></div>';
                append_field += '<div class="chaty-popup-inner popup-pos-bottom">';
                append_field += '<div class="chaty-popup-content">';
                append_field += '<div class="chaty-popup-close">';
                append_field += '<a href="javascript:void(0)" class="close-delete-pop close-chaty-popup-btn right-2 top-2 relative">';
                append_field += '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path d="M15.6 15.5c-.53.53-1.38.53-1.91 0L8.05 9.87 2.31 15.6c-.53.53-1.38.53-1.91 0s-.53-1.38 0-1.9l5.65-5.64L.4 2.4C-.13 1.87-.13 1.02.4.49s1.38-.53 1.91 0l5.64 5.63L13.69.39c.53-.53 1.38-.53 1.91 0s.53 1.38 0 1.91L9.94 7.94l5.66 5.65c.52.53.52 1.38 0 1.91z"/></svg>';
                append_field += '</a>';
                append_field += '</div>';
                append_field += '<div class="a-card a-card--normal">';
                append_field += '<div class="chaty-popup-header text-left font-primary text-cht-gray-150 font-medium p-5 relative">Add Option</div>';
                append_field += '<div class="chaty-popup-body text-cht-gray-150 text-base px-5 py-6">';
                append_field += '<div class="contact-form-dropdown-main">';
                append_field += '<input type="text" name="cht_social_Contact_Us[custom_fields]['+contact_form_field_data+'][dropdown_placeholder]" class="contact-form-dropdown-select" value="- Select -" placeholder="Select...">';
                append_field += '<div class="contact-form-dropdown-option ui-sortable">';
                append_field += '<div class="option-value-field ui-sortable-handle">';
                append_field += '<input type="text" name="cht_social_Contact_Us[custom_fields]['+contact_form_field_data+'][dropdown_option][]" value="" data-id="'+contact_form_field_data+'">';
                append_field += '<span class="add-customfield-dropdown-option" data-field="'+contact_form_field_data+'">Add</span>';
                append_field += '</div>';
                append_field += '</div>';
                append_field += '</div>';
                append_field += '<span class="contact-form-dropdfown-close"><i class="fas fa-times"></i></span>';
                append_field += '</div>';
                append_field += '<div class="chaty-popup-footer flex px-5">';
                append_field += '<button type="button" class="btn rounded-lg btn-dropdown-save">Save</button>';
                append_field += '</div>';
                append_field += '</div>';
                append_field += '</div>';
                append_field += '</div>';
                append_field += '</div>';
                append_field += '</div>';
                append_field += '</div>';


                $(".chaty-default-settings .form-field-setting-col").append(append_field);

                // if(contact_form_field_select == 'dropdown') {
                //     $('.dropdown-setting-label').show();
                // } else {
                //     $('.dropdown-setting-label').hide();
                // }

                $(".custom-dropdown").find(".dropdown-setting-label").show();

                if(contact_form_field_select != 'textarea' && contact_form_field_select != 'dropdown' && contact_form_field_select != 'textblock' ) {
                    if(contact_form_field_select == 'text' ) {
                        var custom_field_placeholder = 'Enter your message';
                    } else if(contact_form_field_select == 'number' ) {
                        var custom_field_placeholder = 'Enter a number';
                    } else if(contact_form_field_select == 'url' ) {
                        var custom_field_placeholder = 'Enter your website';
                    } else if(contact_form_field_select == 'date' ) {
                        var custom_field_placeholder = 'mm/dd/yyyy';
                    }
                    var textbox = $(document.createElement('input')).attr(
                        {
                            'name': 'cht_social_Contact_Us[custom_fields][' + contact_form_field_data + '][placeholder]',
                            'data-id': contact_form_field_data,
                            'placeholder': custom_field_placeholder,
                            'type': 'text',
                            'class': 'contact_form_custom_value',
                            'value': custom_field_placeholder,
                            'data-type': 'text'
                        }

                    );
                    $('[name="cht_social_Contact_Us[custom_fields][' + contact_form_field_data + '][placeholder]"]').replaceWith(textbox);

                    if(contact_form_field_select == 'file' ) {
                        var fileupload = $(document.createElement('input')).attr(
                            {
                                'name': 'cht_social_Contact_Us[custom_fields][' + contact_form_field_data + '][placeholder]',
                                'data-id': contact_form_field_data,
                                'placeholder': custom_field_placeholder,
                                'type': 'file',
                                'class': 'contact_form_custom_value',
                                'style': 'pointer-events: none;',
                                'data-type': 'file'
                            }
                        );

                        $('[name="cht_social_Contact_Us[custom_fields][' + contact_form_field_data + '][placeholder]"]').replaceWith(fileupload);

                    } else {
                    }
                } else if(contact_form_field_select == 'textarea') {
                    var textareabox = '<textarea name="cht_social_Contact_Us[custom_fields][' + contact_form_field_data + '][placeholder]" data-id="'+contact_form_field_data+'" placeholder="Enter your message" class="contact_form_custom_value" rows="5" cols="50" data-type="textarea">Enter your message</textarea>';
                    // var textareabox = $(document.createElement('textarea')).attr(
                    //     {
                    //         'name': 'cht_social_Contact_Us[custom_fields][' + contact_form_field_data + '][placeholder]',
                    //         'data-id': contact_form_field_data,
                    //         'placeholder': 'Enter your message',
                    //         'class': 'contact_form_custom_value',
                    //         'rows': '5',
                    //         'cols': '50',
                    //         'data-type': 'textarea',
                    //         'value': 'Enter your message'
                    //     }
                    // );
                    $('[name="cht_social_Contact_Us[custom_fields][' + contact_form_field_data + '][placeholder]"]').replaceWith(textareabox);

                } else if(contact_form_field_select == 'textblock' ) {
                    var textareabox = $(document.createElement('textarea')).attr(
                        {
                            'name': 'cht_social_Contact_Us[custom_fields][' + contact_form_field_data + '][placeholder]',
                            'data-id': contact_form_field_data,
                            'placeholder': 'Enter your message',
                            'class': 'contact_form_custom_value textblock_custom_editor',
                            'id': 'textblock_editor',
                            'data-type': 'textblock'
                        }
                    );
                    $('[name="cht_social_Contact_Us[custom_fields][' + contact_form_field_data + '][placeholder]"]').replaceWith(textareabox);
                } else {
                    var selectbox = $(document.createElement('select')).attr(
                        {
                            'name': 'cht_social_Contact_Us[custom_fields][' + contact_form_field_data + '][placeholder]',
                            'class': 'contact_form_custom_value',
                            'data-id': contact_form_field_data,
                            'placeholder': 'Select',
                            'data-type': 'select'
                        }
                    );
                    $('[name="cht_social_Contact_Us[custom_fields][' + contact_form_field_data + '][placeholder]').replaceWith(selectbox);

                    $("<select id='contact-form-" + contact_form_field_data + "' class='default_contact_form_input' value=''  data-field='"+ contact_form_field_data +"'><option disabled='' selected=''></option></select>").insertBefore("#contact-form-consent-text");
                }

                if(contact_form_field_select == 'number' ) {
                    var custom_field_text_label = 'Custom Number';
                }else if (contact_form_field_select == 'url' ) {
                    var custom_field_text_label = 'Custom Website';
                }else if (contact_form_field_select == 'textarea' ) {
                    var custom_field_text_label = 'Custom Text Area';
                }else if (contact_form_field_select == 'textblock' ) {
                    var custom_field_text_label = 'Custom Text Block';
                }else if (contact_form_field_select == 'date' ) {
                    var custom_field_text_label = 'Custom Date';
                }else if (contact_form_field_select == 'dropdown' ) {
                    var custom_field_text_label = 'Custom Dropdown';
                }else if (contact_form_field_select == 'file' ) {
                    var custom_field_text_label = 'Custom File Upload';
                }else {
                    var custom_field_text_label = 'Custom Text';
                }
                $('#custom_field_label'+contact_form_field_data).text(custom_field_text_label);
                $('[name="cht_social_Contact_Us[custom_fields][' + contact_form_field_data + '][field_label]"]').val(custom_field_text_label);
                var fieldUniqueId = Math.floor(Math.random() * 26) + Date.now();
                $('#custom_field_label'+contact_form_field_data).closest(".field-setting-col").attr("data-order", fieldUniqueId);
                $('[name="cht_social_Contact_Us[custom_fields]['+contact_form_field_data+'][unique_id]"]').val(fieldUniqueId);

                var custom_text_block_html = document.getElementsByClassName("custom-textblock");
                $(".chaty-default-settings .form-field-setting-col").prepend(custom_text_block_html);
                $(".chaty-default-settings .form-field-setting-col .custom-textblock:last-child").remove();
                setupTextblockEditor();

                $('.contact-form-field-open').remove();
                $('body').removeClass('contact-form-popup-open');

                var cht_mailchimp_mapping_field = "";
                cht_mailchimp_mapping_field += '<div class="mailchimp-field-control chaty-setting-col mapping-field-'+contact_form_field_data+'">';
                cht_mailchimp_mapping_field += '<label class="field-control-title font-primary text-cht-gray-150" for="'+custom_field_text_label+'">'+custom_field_text_label+'</label>';
                cht_mailchimp_mapping_field += '<select class="field-control-dropdown w-full" id="'+custom_field_text_label+'" name="cht_social_Contact_Us[mailchimp-field-mapping]['+custom_field_text_label+']">';
                cht_mailchimp_mapping_field += '</select>';
                cht_mailchimp_mapping_field += '</div>';

                $(".chaty-mailchimp-field-lists").append(cht_mailchimp_mapping_field);

                if($(".chaty-mailchimp-field-lists").find(".chaty-setting-col").length > 1) {
                    $(".chaty-mailchimp-field-lists .chaty-setting-col:last-child select").html($(".chaty-mailchimp-field-lists").find(".chaty-setting-col:first-child select").html());
                    $(".chaty-setting-col:last-child select").val("");
                } else {
                    jQuery.ajax(
                        {
                            url: ajaxurl,
                            type:'post',
                            data: 'action=chaty-mailchimp-field-list&list_id=' + $("#chaty_mailchimp_lists").val() +'&wpnonce=' + cht_settings.ajax_nonce+'&field_name='+custom_field_text_label,
                            success: function ( data ) {
                                if (data != '' ) {
                                    $('.chaty-mailchimp-field-mapping').show();
                                    $('.chaty-mailchimp-field-lists .chaty-setting-col:last-child select').html(data);
                                } else {
                                    $('.chaty-mailchimp-field-mapping').hide();
                                }
                            },
                        }
                    );
                }

                change_custom_preview();
            }
        );

        $(document).on("click", '.custom-stickyelement-delete i.stickyelement-delete' , function (e) {
            if($(this).closest(".field-setting-col").hasClass("custom-textblock")) {
                tinymce.execCommand( 'mceRemoveEditor', false, 'textblock_editor');
            }
            $(this).closest('.field-setting-col').remove();
            $('.chaty-default-settings .form-field-setting-col .chaty-custom-field-limit').remove();
            var delete_id = $(this).closest(".custom-stickyelement-delete").attr("delete-id");
            $(".chaty-mailchimp-field-lists .mapping-field-"+delete_id).remove();
            if($(".chaty-mailchimp-field-lists").find(".chaty-setting-col").length == 0) {
                $('.chaty-mailchimp-field-mapping').hide();
            }

            change_custom_preview();
        });

        $(document).on("mouseenter", "#chaty-social-Contact_Us .form-field-setting-col .field-setting-col", function (){
            $(this).find(".sort-contact-form-field").addClass("active");
        });

        $(document).on("mouseleave", "#chaty-social-Contact_Us .form-field-setting-col .field-setting-col", function (){
            $(this).find(".sort-contact-form-field").removeClass("active");
        });

        jQuery("#chaty-social-Contact_Us .chaty-default-settings .form-field-setting-col").sortable({
            placeholder: "sort-contact-form-field-placeholder",
            handle: ".sort-contact-form-field",
            start: function() {
            },
            stop: function () {
                setupTextblockEditor();
            },
            update: function (event, ui) {
                setupTextblockEditor();
                change_custom_preview();
                save_contact_form_field_order();
            }
        });

        var customfields_wp = $('#chaty-whatsapp-custom-fields-length').val();
        var custom_field_wp = ( typeof customfields_wp !== 'undefined') ? customfields_wp : 1;
        $(document).on("click", '.custom-whatsapp-field-setting a.add-custom-field' , function (e) {
            e.preventDefault();
            var length = $('.chaty-whatsapp-custom-field').length;

            if (length >= 6) {
                $('.pre-filled-message-setting .form-field-setting-col .chaty-custom-field-limit').remove();
                var field = "<div class='chaty-custom-field-limit'><div class='social-channel-popover'><p class='description'>You can add up to 6 custom fields</p></div></div>";
                $('.pre-filled-message-setting .form-field-setting-col').append(field);
                $('.chaty-custom-field-limit .social-channel-popover').show();
            } else {

                var is_active = $(this).data("isactive");
                var active_url_page = $(this).data("active-page-url");

                var  free_version_div = ( is_active == 0 ) ? 'chaty-free-version' : '';

                var upgrad_div = '<div class="upgrade-chaty-link"><a href="'+active_url_page+'" target="_blank"><i class="fas fa-lock"></i>ACTIVATE YOUR KEY</a></div>';

                if(is_active == 1 ) {
                    upgrad_div='';
                }

                var field = "";
                field += "<div class='chaty-popup whatsapp-field-open whatsapp-setting-popup-open active' id='whatsapp_field_open"+custom_field_wp+"'>";
                field += '<div class="chaty-popup-outer"></div>';
                field += '<div class="chaty-popup-inner popup-pos-bottom">';
                field += '<div class="chaty-popup-content">';
                field += '<div class="chaty-popup-close">';
                field += '<a href="javascript:void(0)" class="close-chaty-popup-btn right-2 top-2 relative">';
                field += '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path d="M15.6 15.5c-.53.53-1.38.53-1.91 0L8.05 9.87 2.31 15.6c-.53.53-1.38.53-1.91 0s-.53-1.38 0-1.9l5.65-5.64L.4 2.4C-.13 1.87-.13 1.02.4.49s1.38-.53 1.91 0l5.64 5.63L13.69.39c.53-.53 1.38-.53 1.91 0s.53 1.38 0 1.91L9.94 7.94l5.66 5.65c.52.53.52 1.38 0 1.91z"/></svg>';
                field += '</a>';
                field += '</div>';
                field += '<div class="a-card a-card--normal">';
                field += '<div class="chaty-popup-header text-left font-primary text-cht-gray-150 font-medium p-5 relative">Add new field</div>';
                field += '<div class="chaty-popup-body text-cht-gray-150 text-base px-5 py-6">';
                field += "<div class='whatsapp-field-select-wrap "+free_version_div+"'><label class='whatsapp-field-select'><input type='radio' value='text' data-field='"+custom_field_wp+"' name='whatsapp[custom_fields]["+custom_field_wp+"][field_dropdown]' /><span><i class='fas fa-edit'></i>Text</span></label><label class='whatsapp-field-select'><input type='radio' value='textarea' data-field='"+custom_field_wp+"' name='whatsapp[custom_fields]["+custom_field_wp+"][field_dropdown]' /><span><i class='fas fa-align-justify'></i>Text Area</span></label><label class='whatsapp-field-select'><input type='radio' value='number' data-field='"+custom_field_wp+"' name='whatsapp[custom_fields]["+custom_field_wp+"][field_dropdown]' /><span><i class='fas fa-phone'></i>Number</span></label><label class='whatsapp-field-select'><input type='radio' value='date' data-field='"+custom_field_wp+"' name='whatsapp[custom_fields]["+custom_field_wp+"][field_dropdown]' /><span><i class='fas fa-calendar-week'></i>Date</span></label><label class='whatsapp-field-select'><input type='radio' value='url' data-field='"+custom_field_wp+"' name='whatsapp[custom_fields]["+custom_field_wp+"][field_dropdown]' /><span><i class='fas fa-link'></i>Website</span></label><label class='whatsapp-field-select'><input type='radio' value='dropdown' data-field='"+custom_field_wp+"' name='whatsapp[custom_fields]["+custom_field_wp+"][field_dropdown]' /><span><i class='fas fa-caret-down'></i>Dropdown</span>	</label>"+upgrad_div+"</div></div><div id='whatsapp_custom_dropdown"+custom_field_wp+"' class='whatsapp-dropdown-main' style='display: none;'><input type='text' name='whatsapp[custom_fields]["+custom_field_wp+"][dropdown-placeholder]' class='whatsapp-dropdown-select' value='' placeholder='Select...'/><div class='whatsapp-dropdown-option'><div class='option-value-field'><span class='move-icon'></span><input type='text' name='whatsapp[custom_fields]["+custom_field_wp+"][dropdown-option][]' value=''/><span class='add-whatsapp-customfield-dropdown-option' data-field='"+custom_field_wp+"'>Add</span></div></div><input type='submit' name='submit' class='button button-primary' value='Save'></div><span class='whatsapp-dropdfown-close'><i class='fas fa-times'></i></span>";
                field += '</div>';
                field += '</div>';
                field += '</div>';
                field += '</div>';
                field += '</div>';


                // var field = "<div id='whatsapp_field_open"+custom_field_wp+"' class='whatsapp-field-open whatsapp-setting-popup-open'><div class='whatsapp-popup-label'><h3>Add A New Field</h3><div class='whatsapp-field-select-wrap "+free_version_div+"'><label class='whatsapp-field-select'><input type='radio' value='text' data-field='"+custom_field_wp+"' name='whatsapp[custom_fields]["+custom_field_wp+"][field_dropdown]' /><span><i class='fas fa-edit'></i>Text</span></label><label class='whatsapp-field-select'><input type='radio' value='textarea' data-field='"+custom_field_wp+"' name='whatsapp[custom_fields]["+custom_field_wp+"][field_dropdown]' /><span><i class='fas fa-align-justify'></i>Text Area</span></label><label class='whatsapp-field-select'><input type='radio' value='number' data-field='"+custom_field_wp+"' name='whatsapp[custom_fields]["+custom_field_wp+"][field_dropdown]' /><span><i class='fas fa-phone'></i>Number</span></label><label class='whatsapp-field-select'><input type='radio' value='date' data-field='"+custom_field_wp+"' name='whatsapp[custom_fields]["+custom_field_wp+"][field_dropdown]' /><span><i class='fas fa-calendar-week'></i>Date</span></label><label class='whatsapp-field-select'><input type='radio' value='url' data-field='"+custom_field_wp+"' name='whatsapp[custom_fields]["+custom_field_wp+"][field_dropdown]' /><span><i class='fas fa-link'></i>Website</span></label><label class='whatsapp-field-select'><input type='radio' value='dropdown' data-field='"+custom_field_wp+"' name='whatsapp[custom_fields]["+custom_field_wp+"][field_dropdown]' /><span><i class='fas fa-caret-down'></i>Dropdown</span>	</label>"+upgrad_div+"</div></div><div id='whatsapp_custom_dropdown"+custom_field_wp+"' class='whatsapp-dropdown-main' style='display: none;'><input type='text' name='whatsapp[custom_fields]["+custom_field_wp+"][dropdown-placeholder]' class='whatsapp-dropdown-select' value='' placeholder='Select...'/><div class='whatsapp-dropdown-option'><div class='option-value-field'><span class='move-icon'></span><input type='text' name='whatsapp[custom_fields]["+custom_field_wp+"][dropdown-option][]' value=''/><span class='add-whatsapp-customfield-dropdown-option' data-field='"+custom_field_wp+"'>Add</span></div></div><input type='submit' name='submit' class='button button-primary' value='Save'></div><span class='whatsapp-dropdfown-close'><i class='fas fa-times'></i></span></div>";

                $('.chaty-whatsapp-field-option').append(field);
                $("#whatsapp_field_open"+custom_field_wp).show();
                $('body').addClass('whatsapp-popup-open');

                custom_field_wp++;
            }
        });

        /* Add Dropdown Option */
        $(document).on("click", '.add-whatsapp-customfield-dropdown-option', function (e) {
            var dropdown_custom_field = $(this).data('field');
            $(this).closest('.whatsapp-dropdown-option').append('<div class="option-value-field ui-sortable-handle"><div class="move-icon"></div><input type="text" data-id="'+dropdown_custom_field+'" name="cht_social_Whatsapp[custom_fields]['+dropdown_custom_field+'][dropdown_option][]" value=""/><span class="whatsapp-delete-dropdown-option"><i class="fas fa-times"></i></span></div>');
            setWhatsappDropdownRemoveFn();
        });

        $('.add-whatsapp-customfield-dropdown-option').on("click", function (e) {
            var dropdown_custom_field = $(this).data('field');
            $(this).closest('.whatsapp-dropdown-option').append('<div class="option-value-field ui-sortable-handle"><div class="move-icon"></div><input type="text" data-id="'+dropdown_custom_field+'" name="cht_social_Whatsapp[custom_fields]['+dropdown_custom_field+'][dropdown_option][]" value=""/><span class="whatsapp-delete-dropdown-option"><i class="fas fa-times"></i></span></div>');
            setWhatsappDropdownRemoveFn();
        });

        setWhatsappDropdownRemoveFn();

        /* Open Contact form Field Option popup */
        $(document).on("click", '.whatsapp-field-popup' , function (e) {
                $('#' + $(this).data('id')).show();
                $('body').addClass('whatsapp-popup-open');
            }
        );

        $(document).on("click", '.whatsapp-field-open .whatsapp-dropdfown-close' , function (e) {
                $('.whatsapp-field-open').hide();
                $('body').removeClass('whatsapp-popup-open');
            }
        );

        $(document).on("click", '.chaty-whatsapp-field-option .whatsapp-field-open .whatsapp-dropdfown-close' , function (e) {
                $('.chaty-whatsapp-field-option .whatsapp-field-open').remove();
            }
        );

        $(document).on('click','.whatsapp-dropdown-popup' ,function () {
            $(this).closest('.field-setting-col').find('.whatsapp-dropdown-open').css('display','block');
            $('body').addClass('whatsapp-popup-open');
        });

        $(document).on('click', '.whatsapp-dropdown-open .whatsapp-dropdfown-close',function () {
            $('.whatsapp-dropdown-open').hide();
            $('body').removeClass('whatsapp-popup-open');
        });

        jQuery(document).on('click','.whatsapp-btn-dropdown-save',function (e) {
            var option_lists = [];
            var dropdown_id = 0;
            $(this).closest(".whatsapp-dropdown-open").find(".option-value-field input").each(function (){
                dropdown_id = $(this).data("id");
                option_lists.push($(this).val())
            });
            var dropdown_default = $(this).closest(".whatsapp-dropdown-open").find(".whatsapp-dropdown-select").val();
            $("select[name='cht_social_Whatsapp[custom_fields][" + dropdown_id + "][placeholder]']").html("");
            if(dropdown_default) {
                $("select[name='cht_social_Whatsapp[custom_fields][" + dropdown_id + "][placeholder]']").append("<option>" + dropdown_default + "</option>");
            }
            $(option_lists).each(function (key, value) {
                if(value) {
                    $("select[name='cht_social_Whatsapp[custom_fields][" + dropdown_id + "][placeholder]']").append("<option>" + value + "</option>");
                }
            });
            $('.whatsapp-dropdown-open').hide();
            $('.whatsapp-field-open.whatsapp-setting-popup-open').hide();
            $('body').removeClass('whatsapp-popup-open');
            change_custom_preview();
        });

        jQuery('.whatsapp-btn-dropdown-save').on('click', function (e) {
            var option_lists = [];
            var dropdown_id = 0;
            $(this).closest(".whatsapp-dropdown-open").find(".option-value-field input").each(function (){
                dropdown_id = $(this).data("id");
                option_lists.push($(this).val())
            });
            var dropdown_default = $(this).closest(".whatsapp-dropdown-open").find(".whatsapp-dropdown-select").val();
            $("select[name='cht_social_Whatsapp[custom_fields][" + dropdown_id + "][placeholder]']").html("");
            if(dropdown_default) {
                $("select[name='cht_social_Whatsapp[custom_fields][" + dropdown_id + "][placeholder]']").append("<option>" + dropdown_default + "</option>");
            }
            $(option_lists).each(function (key, value) {
                if(value) {
                    $("select[name='cht_social_Whatsapp[custom_fields][" + dropdown_id + "][placeholder]']").append("<option>" + value + "</option>");
                }
            });
            $('.whatsapp-dropdown-open').hide();
            $('.whatsapp-field-open.whatsapp-setting-popup-open').hide();
            $('body').removeClass('whatsapp-popup-open');
            change_custom_preview();
        });

        $(document).on(
            'mouseup', function ( event ) {
                if (!$(event.target).closest(".chaty-whatsapp-field-option .whatsapp-field-open, .whatsapp-field-popup,.whatsapp-dropdown-popup, .whatsapp-dropdown-open, .whatsapp-setting-popup-open").length ) {
                    $('.whatsapp-field-open').hide();
                    $('.whatsapp-dropdown-open').hide();
                    $('body').removeClass('whatsapp-popup-open');
                }
            }
        );

        $(document).on("change", '.whatsapp-field-select input[type="radio"]' , function (e) {

                var whatsapp_field_select = $(this).val();
                var whatsapp_field_data = $(this).data('field');

                var append_field = "";
                append_field += '<div class="chaty-separator mt-2.5"></div>';
                append_field += '<div class="field-setting-col chaty-whatsapp-custom-field custom-'+whatsapp_field_select+'">';
                append_field += '<input type="hidden" name="cht_social_Whatsapp[custom_fields]['+whatsapp_field_data+'][is_active]" value="no">';
                append_field += '<input type="hidden" name="cht_social_Whatsapp[custom_fields]['+whatsapp_field_data+'][is_required]" value="no">';
                append_field += '<input type="hidden" name="cht_social_Whatsapp[custom_fields]['+whatsapp_field_data+'][field_dropdown]" value="'+whatsapp_field_select+'">';
                append_field += '<div class="label-flex mb-4">';
                append_field += '<label class="chaty-switch chaty-switch-toggle text-cht-gray-150 text-base" for="field_for_Whatsapp_'+whatsapp_field_data+'">';
                append_field += '<input type="checkbox" class="chaty-field-setting" name="cht_social_Whatsapp[custom_fields]['+whatsapp_field_data+'][is_active]" id="field_for_Contact_Us_'+whatsapp_field_data+'" value="yes" checked>';
                append_field += '<div class="chaty-slider round"></div>';
                append_field += '<span class="field-label"><span id="custom_field_label_whatsapp'+whatsapp_field_data+'">Custom Text</span>';
                append_field += '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"> <g clip-path="url(#clip0_8719_30645)"> <path d="M7.33398 2.66699H2.66732C2.3137 2.66699 1.97456 2.80747 1.72451 3.05752C1.47446 3.30756 1.33398 3.6467 1.33398 4.00033V13.3337C1.33398 13.6873 1.47446 14.0264 1.72451 14.2765C1.97456 14.5265 2.3137 14.667 2.66732 14.667H12.0006C12.3543 14.667 12.6934 14.5265 12.9435 14.2765C13.1935 14.0264 13.334 13.6873 13.334 13.3337V8.66699" stroke="#C6D7E3" stroke-width="1.33" stroke-linecap="round" stroke-linejoin="round"/> <path d="M12.334 1.66617C12.5992 1.40095 12.9589 1.25195 13.334 1.25195C13.7091 1.25195 14.0688 1.40095 14.334 1.66617C14.5992 1.93138 14.7482 2.29109 14.7482 2.66617C14.7482 3.04124 14.5992 3.40095 14.334 3.66617L8.00065 9.9995L5.33398 10.6662L6.00065 7.9995L12.334 1.66617Z" stroke="#C6D7E3" stroke-width="1.33" stroke-linecap="round" stroke-linejoin="round"/> </g> <defs> <clipPath id="clip0_8719_30645"> <rect width="16" height="16" fill="white"/> </clipPath> </defs> </svg>';
                append_field += '</span>';
                append_field += '</label>';
                append_field += '<div class="label-input">';
                append_field += '<input type="text" class="label-input-field chaty-input-text" value="Custom Text" name="cht_social_Whatsapp[custom_fields]['+whatsapp_field_data+'][field_label]" />';
                append_field += '</div>';
                append_field += '</div>';
                append_field += '<div class="field-settings active">';
                append_field += '<div class="chaty-setting-col sm:grid grid-cols-2 items-center gap-3">';
                append_field += '<div>';
                append_field += '<input class="rounded-lg w-full chaty-input-text whatsapp_field_custom_value" id="placeholder_for_Whatsapp_'+whatsapp_field_data+'" type="text" name="cht_social_Whatsapp[custom_fields]['+whatsapp_field_data+'][placeholder]" value="Enter Text" data-type="'+whatsapp_field_select+'">';
                append_field += '</div>';
                append_field += '<div class="flex items-center space-x-3">';
                append_field += '<div class="checkbox">';
                append_field += '<label for="field_required_for_Contact_Us_'+whatsapp_field_data+'" class="chaty-checkbox text-cht-gray-150 text-base flex items-center">';
                append_field += '<input class="sr-only" type="checkbox" id="field_required_for_Contact_Us_'+whatsapp_field_data+'" name="cht_social_Whatsapp[custom_fields]['+whatsapp_field_data+'][is_required]" value="yes" checked />';
                append_field += '<span class="mr-2"></span>';
                append_field += 'Required';
                append_field += '</label>';
                append_field += '</div>';
                append_field += '<div id="setting_label'+whatsapp_field_data+'" class="dropdown-setting-label" style="display: none;">';
                append_field += '<a class="flex items-center space-x-1.5 whatsapp-dropdown-popup" href="javascript:;">';
                append_field += '<span><svg width="16" height="16" viewBox="0 0 16 16" fill="none"> <path d="M8 10C9.10457 10 10 9.10457 10 8C10 6.89543 9.10457 6 8 6C6.89543 6 6 6.89543 6 8C6 9.10457 6.89543 10 8 10Z" stroke="currentColor" stroke-width="1.33" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M12.9332 9.99984C12.8444 10.2009 12.818 10.424 12.8572 10.6402C12.8964 10.8565 12.9995 11.056 13.1532 11.2132L13.1932 11.2532C13.3171 11.377 13.4155 11.5241 13.4826 11.6859C13.5497 11.8478 13.5842 12.0213 13.5842 12.1965C13.5842 12.3717 13.5497 12.5452 13.4826 12.7071C13.4155 12.869 13.3171 13.016 13.1932 13.1398C13.0693 13.2638 12.9223 13.3621 12.7604 13.4292C12.5986 13.4963 12.4251 13.5309 12.2498 13.5309C12.0746 13.5309 11.9011 13.4963 11.7392 13.4292C11.5774 13.3621 11.4303 13.2638 11.3065 13.1398L11.2665 13.0998C11.1094 12.9461 10.9098 12.843 10.6936 12.8038C10.4773 12.7646 10.2542 12.7911 10.0532 12.8798C9.85599 12.9643 9.68782 13.1047 9.56937 13.2835C9.45092 13.4624 9.38736 13.672 9.3865 13.8865V13.9998C9.3865 14.3535 9.24603 14.6926 8.99598 14.9426C8.74593 15.1927 8.40679 15.3332 8.05317 15.3332C7.69955 15.3332 7.36041 15.1927 7.11036 14.9426C6.86031 14.6926 6.71984 14.3535 6.71984 13.9998V13.9398C6.71467 13.7192 6.64325 13.5052 6.51484 13.3256C6.38644 13.1461 6.20699 13.0094 5.99984 12.9332C5.79876 12.8444 5.57571 12.818 5.35944 12.8572C5.14318 12.8964 4.94362 12.9995 4.7865 13.1532L4.7465 13.1932C4.62267 13.3171 4.47562 13.4155 4.31376 13.4826C4.15189 13.5497 3.97839 13.5842 3.80317 13.5842C3.62795 13.5842 3.45445 13.5497 3.29258 13.4826C3.13072 13.4155 2.98367 13.3171 2.85984 13.1932C2.73587 13.0693 2.63752 12.9223 2.57042 12.7604C2.50332 12.5986 2.46879 12.4251 2.46879 12.2498C2.46879 12.0746 2.50332 11.9011 2.57042 11.7392C2.63752 11.5774 2.73587 11.4303 2.85984 11.3065L2.89984 11.2665C3.05353 11.1094 3.15663 10.9098 3.19584 10.6936C3.23505 10.4773 3.20858 10.2542 3.11984 10.0532C3.03533 9.85599 2.89501 9.68782 2.71615 9.56937C2.53729 9.45092 2.32769 9.38736 2.11317 9.3865H1.99984C1.64622 9.3865 1.30708 9.24603 1.05703 8.99598C0.80698 8.74593 0.666504 8.40679 0.666504 8.05317C0.666504 7.69955 0.80698 7.36041 1.05703 7.11036C1.30708 6.86031 1.64622 6.71984 1.99984 6.71984H2.05984C2.2805 6.71467 2.49451 6.64325 2.67404 6.51484C2.85357 6.38644 2.99031 6.20699 3.0665 5.99984C3.15525 5.79876 3.18172 5.57571 3.14251 5.35944C3.10329 5.14318 3.00019 4.94362 2.8465 4.7865L2.8065 4.7465C2.68254 4.62267 2.58419 4.47562 2.51709 4.31376C2.44999 4.15189 2.41545 3.97839 2.41545 3.80317C2.41545 3.62795 2.44999 3.45445 2.51709 3.29258C2.58419 3.13072 2.68254 2.98367 2.8065 2.85984C2.93033 2.73587 3.07739 2.63752 3.23925 2.57042C3.40111 2.50332 3.57462 2.46879 3.74984 2.46879C3.92506 2.46879 4.09856 2.50332 4.26042 2.57042C4.42229 2.63752 4.56934 2.73587 4.69317 2.85984L4.73317 2.89984C4.89029 3.05353 5.08985 3.15663 5.30611 3.19584C5.52237 3.23505 5.74543 3.20858 5.9465 3.11984H5.99984C6.19702 3.03533 6.36518 2.89501 6.48363 2.71615C6.60208 2.53729 6.66565 2.32769 6.6665 2.11317V1.99984C6.6665 1.64622 6.80698 1.30708 7.05703 1.05703C7.30708 0.80698 7.64621 0.666504 7.99984 0.666504C8.35346 0.666504 8.6926 0.80698 8.94264 1.05703C9.19269 1.30708 9.33317 1.64622 9.33317 1.99984V2.05984C9.33402 2.27436 9.39759 2.48395 9.51604 2.66281C9.63449 2.84167 9.80266 2.98199 9.99984 3.0665C10.2009 3.15525 10.424 3.18172 10.6402 3.14251C10.8565 3.10329 11.056 3.00019 11.2132 2.8465L11.2532 2.8065C11.377 2.68254 11.5241 2.58419 11.6859 2.51709C11.8478 2.44999 12.0213 2.41545 12.1965 2.41545C12.3717 2.41545 12.5452 2.44999 12.7071 2.51709C12.869 2.58419 13.016 2.68254 13.1398 2.8065C13.2638 2.93033 13.3621 3.07739 13.4292 3.23925C13.4963 3.40111 13.5309 3.57462 13.5309 3.74984C13.5309 3.92506 13.4963 4.09856 13.4292 4.26042C13.3621 4.42229 13.2638 4.56934 13.1398 4.69317L13.0998 4.73317C12.9461 4.89029 12.843 5.08985 12.8038 5.30611C12.7646 5.52237 12.7911 5.74543 12.8798 5.9465V5.99984C12.9643 6.19702 13.1047 6.36518 13.2835 6.48363C13.4624 6.60208 13.672 6.66565 13.8865 6.6665H13.9998C14.3535 6.6665 14.6926 6.80698 14.9426 7.05703C15.1927 7.30708 15.3332 7.64621 15.3332 7.99984C15.3332 8.35346 15.1927 8.6926 14.9426 8.94264C14.6926 9.19269 14.3535 9.33317 13.9998 9.33317H13.9398C13.7253 9.33402 13.5157 9.39759 13.3369 9.51604C13.158 9.63449 13.0177 9.80266 12.9332 9.99984V9.99984Z" stroke="currentColor" stroke-width="1.33" stroke-linecap="round" stroke-linejoin="round"></path> </svg></span>';
                append_field += '<span>Settings</span>';
                append_field += '</a>';
                append_field += '</div>';
                append_field += '<div class="custom-field-remove">';
                append_field += '<span class="whatsapp-custom-stickyelement-delete" delete-id="'+whatsapp_field_data+'">';
                append_field += '<i class="fas fa-trash-alt stickyelement-delete"></i>';
                append_field += '</span>';
                append_field += '</div>';
                append_field += '</div>';
                append_field += '</div>';
                append_field += '</div>';
                append_field += '<div class="chaty-popup whatsapp-dropdown-open whatsapp-setting-popup-open">';
                append_field += '<div class="chaty-popup-outer"></div>';
                append_field += '<div class="chaty-popup-inner popup-pos-bottom">';
                append_field += '<div class="chaty-popup-content">';
                append_field += '<div class="chaty-popup-close">';
                append_field += '<a href="javascript:void(0)" class="close-delete-pop close-chaty-popup-btn right-2 top-2 relative">';
                append_field += '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path d="M15.6 15.5c-.53.53-1.38.53-1.91 0L8.05 9.87 2.31 15.6c-.53.53-1.38.53-1.91 0s-.53-1.38 0-1.9l5.65-5.64L.4 2.4C-.13 1.87-.13 1.02.4.49s1.38-.53 1.91 0l5.64 5.63L13.69.39c.53-.53 1.38-.53 1.91 0s.53 1.38 0 1.91L9.94 7.94l5.66 5.65c.52.53.52 1.38 0 1.91z"/></svg>';
                append_field += '</a>';
                append_field += '</div>';
                append_field += '<div class="a-card a-card--normal">';
                append_field += '<div class="chaty-popup-header text-left font-primary text-cht-gray-150 font-medium p-5 relative">Add Option</div>';
                append_field += '<div class="chaty-popup-body text-cht-gray-150 text-base px-5 py-6">';
                append_field += '<div class="whatsapp-dropdown-main">';
                append_field += '<input type="text" name="cht_social_Whatsapp[custom_fields]['+whatsapp_field_data+'][dropdown_placeholder]" class="whatsapp-dropdown-select" value="- Select -" placeholder="Select...">';
                append_field += '<div class="whatsapp-dropdown-option ui-sortable">';
                append_field += '<div class="option-value-field ui-sortable-handle">';
                append_field += '<input type="text" name="cht_social_Whatsapp[custom_fields]['+whatsapp_field_data+'][dropdown_option][]" value="" data-id="'+whatsapp_field_data+'">';
                append_field += '<span class="add-whatsapp-customfield-dropdown-option" data-field="'+whatsapp_field_data+'">Add</span>';
                append_field += '</div>';
                append_field += '</div>';
                append_field += '</div>';
                append_field += '<span class="whatsapp-dropdfown-close"><i class="fas fa-times"></i></span>';
                append_field += '</div>';
                append_field += '<div class="chaty-popup-footer flex px-5">';
                append_field += '<button type="button" class="btn rounded-lg whatsapp-btn-dropdown-save">Save</button>';
                append_field += '</div>';
                append_field += '</div>';
                append_field += '</div>';
                append_field += '</div>';
                append_field += '</div>';
                append_field += '</div>';
                append_field += '</div>';


                $(".pre-filled-message-setting .form-field-setting-col").append(append_field);

                // if(whatsapp_field_select == 'dropdown') {
                //     $('.dropdown-setting-label').show();
                // } else {
                //     $('.dropdown-setting-label').hide();
                // }

                $(".custom-dropdown").find(".dropdown-setting-label").show();

                if(whatsapp_field_select != 'textarea' && whatsapp_field_select != 'dropdown' && whatsapp_field_select != 'textblock' ) {
                    if(whatsapp_field_select == 'text' ) {
                        var custom_field_placeholder = 'Enter your message';
                    } else if(whatsapp_field_select == 'number' ) {
                        var custom_field_placeholder = 'Enter a number';
                    } else if(whatsapp_field_select == 'url' ) {
                        var custom_field_placeholder = 'Enter your website';
                    } else if(whatsapp_field_select == 'date' ) {
                        var custom_field_placeholder = 'mm/dd/yyyy';
                    }
                    var textbox = $(document.createElement('input')).attr(
                        {
                            'name': 'cht_social_Whatsapp[custom_fields][' + whatsapp_field_data + '][placeholder]',
                            'data-id': whatsapp_field_data,
                            'placeholder': custom_field_placeholder,
                            'type': 'text',
                            'class': 'whatsapp_custom_value',
                            'value': custom_field_placeholder,
                            'data-type': 'text'
                        }

                    );
                    $('[name="cht_social_Whatsapp[custom_fields][' + whatsapp_field_data + '][placeholder]"]').replaceWith(textbox);

                    if(whatsapp_field_select == 'file' ) {
                        var fileupload = $(document.createElement('input')).attr(
                            {
                                'name': 'cht_social_Whatsapp[custom_fields][' + whatsapp_field_data + '][placeholder]',
                                'data-id': whatsapp_field_data,
                                'placeholder': custom_field_placeholder,
                                'type': 'file',
                                'class': 'whatsapp_custom_value',
                                'style': 'pointer-events: none;',
                                'data-type': 'file'
                            }
                        );

                        $('[name="cht_social_Whatsapp[custom_fields][' + whatsapp_field_data + '][placeholder]"]').replaceWith(fileupload);

                    } else {
                    }
                } else if(whatsapp_field_select == 'textarea') {
                    var textareabox = '<textarea name="cht_social_Whatsapp[custom_fields][' + whatsapp_field_data + '][placeholder]" data-id="'+whatsapp_field_data+'" placeholder="Enter your message" class="whatsapp_custom_value" rows="5" cols="50" data-type="textarea">Enter your message</textarea>';
                    // var textareabox = $(document.createElement('textarea')).attr(
                    //     {
                    //         'name': 'cht_social_Whatsapp[custom_fields][' + whatsapp_field_data + '][placeholder]',
                    //         'data-id': whatsapp_field_data,
                    //         'placeholder': 'Enter your message',
                    //         'class': 'whatsapp_custom_value',
                    //         'rows': '5',
                    //         'cols': '50',
                    //     }
                    // );
                    $('[name="cht_social_Whatsapp[custom_fields][' + whatsapp_field_data + '][placeholder]"]').replaceWith(textareabox);

                } else if(whatsapp_field_select == 'textblock' ) {
                    // var textareabox = $(document.createElement('textarea')).attr(
                    //     {
                    //         'name': 'cht_social_Whatsapp[custom_fields][' + whatsapp_field_data + '][placeholder]',
                    //         'data-id': whatsapp_field_data,
                    //         'placeholder': 'Enter your message',
                    //         'class': 'whatsapp_custom_value',
                    //         'id': 'textblock_editor'
                    //     }
                    // );
                    // $('[name="cht_social_Whatsapp[custom_fields][' + whatsapp_field_data + '][placeholder]"]').replaceWith(textareabox);
                    // tinymce.execCommand( 'mceRemoveEditor', true, 'textblock_editor');
                    // tinymce.execCommand( 'mceAddEditor', true, 'textblock_editor');
                } else {
                    var selectbox = $(document.createElement('select')).attr(
                        {
                            'name': 'cht_social_Whatsapp[custom_fields][' + whatsapp_field_data + '][placeholder]',
                            'class': 'whatsapp_custom_value',
                            'data-id': whatsapp_field_data,
                            'placeholder': 'Select',
                            'data-type': 'select'
                        }
                    );
                    $('[name="cht_social_Whatsapp[custom_fields][' + whatsapp_field_data + '][placeholder]').replaceWith(selectbox);

                    $("<select id='whatsapp-" + whatsapp_field_data + "' class='default_contact_form_input' data-field='"+ whatsapp_field_data +"'><option disabled='' selected=''></option></select>").insertBefore("#whatsapp-consent-text");
                }

                if(whatsapp_field_select == 'number' ) {
                    var custom_field_text_label = 'Custom Number';
                }else if (whatsapp_field_select == 'url' ) {
                    var custom_field_text_label = 'Custom Website';
                }else if (whatsapp_field_select == 'textarea' ) {
                    var custom_field_text_label = 'Custom Text Area';
                }else if (whatsapp_field_select == 'textblock' ) {
                    var custom_field_text_label = 'Custom Text Block';
                }else if (whatsapp_field_select == 'date' ) {
                    var custom_field_text_label = 'Custom Date';
                }else if (whatsapp_field_select == 'dropdown' ) {
                    var custom_field_text_label = 'Custom Dropdown';
                }else if (whatsapp_field_select == 'file' ) {
                    var custom_field_text_label = 'Custom File Upload';
                }else {
                    var custom_field_text_label = 'Custom Text';
                }
                $('#custom_field_label_whatsapp'+whatsapp_field_data).text(custom_field_text_label);
                $('[name="cht_social_Whatsapp[custom_fields][' + whatsapp_field_data + '][field_label]"]').val(custom_field_text_label);

                $('.whatsapp-field-open').remove();
                $('body').removeClass('whatsapp-popup-open');
                change_custom_preview();
            }
        );

        $(document).on("click", '.whatsapp-custom-stickyelement-delete i.stickyelement-delete' , function (e) {
            $(this).closest('.field-setting-col').remove();
            $('.pre-filled-message-setting .form-field-setting-col .chaty-custom-field-limit').remove();
            change_custom_preview();
        });

        $(document).on("keyup", "#wp_popup_headline, #wp_popup_nickname", function (){
            change_custom_preview();
        });

        var customImageFor = "";

        $(document).on("click", ".upload-wp-profile", function (){
            customImageFor = $(this).data("for");
            var image = wp.media({
                title: 'Select Whatsapp Profile',
                multiple: false,
                library: {
                    type: 'image',
                }
            }).open()
                .on('select', function (e) {
                    var uploaded_image = image.state().get('selection').first();
                    imageData = uploaded_image.toJSON();
                    $("#"+customImageFor+"-custom-image-upload .img-value").val(imageData.url);
                    $("#"+customImageFor+"-custom-image-upload").addClass("active");
                    $("#"+customImageFor+"-custom-image-upload .image-info").html("<img src='"+imageData.url+"'>");
                    change_custom_preview();
            });
        });

        $(document).on("click", ".remove-custom-img", function (){
            $(this).closest(".custom-img-upload").removeClass("active");
            $(this).closest(".custom-img-upload").find(".image-info").html("");
            $(this).closest(".custom-img-upload").find(".img-value").val("");
            change_custom_preview();
        });

        setupTextblockEditor();

    });

    function setDropDownRemoveFn() {
        $(document).on("click", '.delete-dropdown-option' ,function (e) {
            $(this).closest('div').remove();
        });

        $(".delete-dropdown-option").on("click", function (){
            $(this).closest('div').remove();
        });
    }

    function setWhatsappDropdownRemoveFn() {
        $(document).on("click", '.whatsapp-delete-dropdown-option' ,function (e) {
            $(this).closest('div').remove();
        });

        $('.whatsapp-delete-dropdown-option').click(function (e) {
            $(this).closest('div').remove();
        });
    }

}(jQuery));

function setupTextblockEditor() {
    wp.editor.remove('textblock_editor');
    wp.editor.initialize(
        'textblock_editor',
        {
            tinymce: {
                init_instance_callback : function(editor) {
                    editor.on('KeyUp Change', function (e) {
                        var content = editor.getContent();
                        change_custom_preview();
                        setTextblockEditorText(content);
                    });
                },
                menubar: false,
                wpautop:true,
                branding: false,
                plugins : 'charmap colorpicker compat3x directionality fullscreen hr image lists media paste tabfocus textcolor wordpress wpautoresize wpdialogs wpeditimage wpemoji wpgallery wplink wptextpattern wpview',
                toolbar1: 'bold, italic, underline, link, numlist bullist, forecolor, fontsizeselect'
            },
            quicktags: true
        }
    );
    jQuery('#wp-textblock_editor-wrap .wp-editor-tabs').hide();
}

function setPagesField(eleId, searchType, defaultText) {
    jQuery("#"+eleId).select2({
        tags: false,
        multiple: true,
        minimumInputLength: 2,
        minimumResultsForSearch: 10,
        placeholder: defaultText,
        ajax: {
            url: ajaxurl,
            dataType: "json",
            type: "POST",
            quietMillis: 50,
            data: function (params) {
                var queryParameters = {
                    action: 'search_chaty_field',
                    type: searchType,
                    search: params.term
                }
                return queryParameters;
            },
            processResults: function (data) {
                if(data.length) {
                    return {
                        results: jQuery.map(data, function (item) {
                            return {
                                text: item.title,
                                id: item.id
                            }
                        })
                    };
                } else {
                    return {
                        results: jQuery.map(data, function (item) {
                            return {
                                text: "No results are found",
                                id: 0,
                                disabled: true
                            }
                        })
                    };
                }
            }
        }
    });
}

var totalPageOptions = 0;
var pageOptionContent = "";
var totalDateAndTimeOptions = 0;
var dateAndTimeOptionContent = "";
var totalTrafficOptions = 0;
var tafficOptionContent = "";
jQuery(document).ready(function () {
    totalPageOptions = parseInt(jQuery(".chaty-page-option").length);
    pageOptionContent = jQuery(".chaty-page-options-html").html();
    jQuery(".chaty-page-options-html").remove();
    totalDateAndTimeOptions = parseInt(jQuery(".chaty-date-time-option").length);
    dateAndTimeOptionContent = jQuery(".chaty-date-and-time-options-html").html();
    jQuery(".chaty-date-and-time-options-html").remove();

    totalTrafficOptions = parseInt(jQuery(".custom-traffic-rule").length);
    tafficOptionContent = jQuery(".custom-traffic-rules-html").html();
    jQuery(".custom-traffic-rules-html").remove();

    jQuery(document).on("click", ".mail-merge-tags span", function(e){
        var fieldVal = jQuery.trim(jQuery(this).closest(".chaty-setting-col").find("input[type='text']").val());
        fieldVal += " "+jQuery(this).text();
        jQuery(this).closest(".chaty-setting-col").find("input[type='text']").val(jQuery.trim(fieldVal));
    });

    jQuery("#add-traffic-rule").on("click", function () {
        appendHtml = tafficOptionContent.replace(/__count__/g, totalTrafficOptions, tafficOptionContent);
        jQuery(".traffic-custom-rules-box").append(appendHtml);
        jQuery("#url_option_"+totalTrafficOptions).SumoSelect();
        totalTrafficOptions++;
    });

    jQuery(document).on("click", "#create-date-rule", function(e){
        jQuery("#date-schedule").addClass("active");
        jQuery("#cht_date_rules").val("yes");
    });
    jQuery(document).on("click", "#remove-date-rule", function(e){
        jQuery("#date-schedule").removeClass("active");
        jQuery("#cht_date_rules").val("no");
    });
    if(jQuery("#date_start_time").length && jQuery("#date_end_time").length) {
        jQuery("#date_start_time, #date_end_time").timepicker({
            showLeadingZero: true
        });
    }

    if(jQuery("#date_start_date").length) {
        jQuery("#date_start_date").datepicker({
            dateFormat: 'mm/dd/yy',
            altFormat: 'mm/dd/yy',
            onSelect: function(d,i){
                var minDate = jQuery("#date_start_date").datepicker('getDate');
                minDate.setDate(minDate.getDate()); //add two days
                jQuery("#date_end_date").datepicker("option", "minDate", minDate);
                if(jQuery("#date_end_date").val() <= jQuery("#date_start_date").val()) {
                    jQuery("#date_end_date").val(jQuery("#date_start_date").val());
                }

                if(jQuery("#date_end_date").val() == "") {
                    jQuery("#date_end_date").val(jQuery("#date_start_date").val());
                }
            }
        });
    }
    if(jQuery("#date_end_date").length) {
        jQuery("#date_end_date").datepicker({
            dateFormat: 'mm/dd/yy',
            altFormat: 'mm/dd/yy',
            onSelect: function(d,i){
                if(jQuery("#date_start_date").val() == "") {
                    jQuery("#date_start_date").val(jQuery("#date_end_date").val());
                }
            }
        });
    }
    if(jQuery("#date_start_date").length) {
        if(jQuery("#date_start_date").val() != "") {
            var minDate = jQuery("#date_start_date").datepicker('getDate');
            minDate.setDate(minDate.getDate()); //add two days
            jQuery("#date_end_date").datepicker("option", "minDate", minDate);
            if(jQuery("#date_end_date").val() <= jQuery("#date_start_date").val()) {
                jQuery("#date_end_date").val(jQuery("#date_start_date").val());
            }
        }
    }

    jQuery(document).on("click", "#update-chaty-traffic-source-rule", function(e){
        jQuery(".traffic-options-box").addClass("active");
        jQuery("#chaty_traffic_source").val("yes");
    });
    jQuery(document).on("click", "#remove-traffic-rules", function(e){
        jQuery(".traffic-options-box").removeClass("active");
        jQuery("#chaty_traffic_source").val("no");
    });
    jQuery(document).on("click", ".remove-traffic-option", function(e){
        jQuery(this).closest(".custom-traffic-rule").remove();
    });
    
    jQuery('#create-country-rule').on('click', function() {
        const $parent = jQuery(this).parents('.country-option-box');
        $parent.find('.country-list-box').removeClass('hidden');
        $parent.addClass('show-remove-rules-btn');
    })

    jQuery('#remove-country-rules').on('click', function() {
        const $parent = jQuery(this).parents('.country-option-box');
        jQuery("select.country-list")[0].sumo.unSelectAll();
        jQuery(".country-list option:selected").prop("selected", false);
        jQuery(".country-list").trigger("change");
        $parent.find('.country-list-box').addClass('hidden');
        $parent.removeClass('show-remove-rules-btn')
    })

    jQuery("select#cht_widget_language").on("change", function(){
        jQuery(".page-url-title").text(jQuery("select#cht_widget_language option:selected").data("url"));
    });

    jQuery("#create-rule").on("click", function () {
        appendHtml = pageOptionContent.replace(/__count__/g, totalPageOptions, pageOptionContent);
        jQuery(".chaty-page-options").append(appendHtml);
        jQuery(".chaty-page-options .chaty-page-option").removeClass("last");
        jQuery(".chaty-page-options .chaty-page-option:last").addClass("last");

        const $parent   = jQuery(this).parents('.chaty-option-box');
        const status    =  $parent.find('.chaty-page-option').length > 0;
        $parent.toggleClass('show-remove-rules-btn', status );

        if (jQuery("#is_pro_plugin").val() == "0") {
            jQuery(".chaty-page-options").find("input").attr("name", "");
            jQuery(".chaty-page-options").find("select").attr("name", "");
            jQuery(".chaty-page-options").find("input").removeClass("cht-required");
            jQuery(".chaty-page-options").find("select").removeClass("cht-required");
        } else {
            jQuery("#url_shown_on_"+totalPageOptions+"_option").SumoSelect({
                search: false,
            });
            jQuery("#url_rules_"+totalPageOptions+"_option").SumoSelect({
                search: false,
            });

            if(jQuery("#url_rules_"+totalPageOptions+"_page_ids").length) {
                var eleId = "url_rules_"+totalPageOptions+"_page_ids";
                setPagesField(eleId, "pages", "Select Pages");
            }

            if(jQuery("#url_rules_"+totalPageOptions+"_post_ids").length) {
                var eleId = "url_rules_"+totalPageOptions+"_post_ids";
                setPagesField(eleId, "posts", "Select Posts");
            }

            if(jQuery("#url_rules_"+totalPageOptions+"_category_ids").length) {
                var eleId = "url_rules_"+totalPageOptions+"_category_ids";
                setPagesField(eleId, "categories", "Select Categories");
            }

            if(jQuery("#url_rules_"+totalPageOptions+"_tag_ids").length) {
                var eleId = "url_rules_"+totalPageOptions+"_tag_ids";
                setPagesField(eleId, "tags", "Select Tags");
            }

            if(jQuery("#url_rules_"+totalPageOptions+"_products_ids").length) {
                var eleId = "url_rules_"+totalPageOptions+"_products_ids";
                setPagesField(eleId, "products", "Select Products");
            }

            if(jQuery("#url_rules_"+totalPageOptions+"_wc_products_ids").length) {
                var eleId = "url_rules_"+totalPageOptions+"_wc_products_ids";
                setPagesField(eleId, "sale_products", "Select Products");
            }
        }
        jQuery("#url_"+totalPageOptions+"_option").SumoSelect();

        if(jQuery("select#cht_widget_language").length) {
            jQuery(".page-url-title").text(jQuery("select#cht_widget_language option:selected").data("url"));
        }

        totalPageOptions++;
    });

    if(jQuery(".traffic-url-options").length) {
        jQuery(".traffic-url-options").SumoSelect();
    }

    jQuery("#create-data-and-time-rule").on("click", function () {
        appendHtml = dateAndTimeOptionContent.replace(/__count__/g, totalDateAndTimeOptions, dateAndTimeOptionContent);
        jQuery(".chaty-data-and-time-rules").append(appendHtml);
        jQuery(".chaty-data-and-time-rules .chaty-date-time-option").removeClass("last");
        jQuery(".chaty-data-and-time-rules .chaty-date-time-option:last").addClass("last");
        jQuery(".chaty-data-and-time-rules .chaty-date-time-option").removeClass("first");
        jQuery(".chaty-data-and-time-rules .chaty-date-time-option:first").addClass("first");

        if (jQuery("#is_pro_plugin").val() == "0") {
            jQuery(".chaty-data-and-time-rules").find("input").attr("name", "");
            jQuery(".chaty-data-and-time-rules").find("select").attr("name", "");
            jQuery(".chaty-data-and-time-rules").find("input").removeClass("cht-required");
            jQuery(".chaty-data-and-time-rules").find("select").removeClass("cht-required");
        }
        jQuery("#start_time_"+totalDateAndTimeOptions+", #end_time_"+totalDateAndTimeOptions).timepicker({
            showLeadingZero: true
        });
        jQuery('#gmt_'+totalDateAndTimeOptions+'_option').SumoSelect({
            search: true
        });
        // jQuery('#gmt_'+totalDateAndTimeOptions+'_option').on('select2:open', function(e) {
        //     jQuery("body").addClass("no-checkbox");
        // });

        // trigger this method to move "add rule" button to the last card
        const $current  = jQuery('.chaty-data-and-time-rules .chaty-date-time-option:last');
        const $scope    = jQuery(this).parents('.chaty-option-box');
        wp.hooks.doAction('chaty.days_and_hours_add_rule', {
            $scope,
            $current
        });

        jQuery("#url_shown_on_"+totalDateAndTimeOptions+"_option").SumoSelect();

        totalDateAndTimeOptions++;

    });

    // trigger this method to move "add rule" button to the last card
    const $current  = jQuery('.chaty-data-and-time-rules .chaty-date-time-option:last');
    const $scope    = jQuery('.chaty-data-and-time-rules').parents('.chaty-option-box');
    wp.hooks.doAction('chaty.days_and_hours_add_rule', {
        $scope,
        $current
    } )

    jQuery(".chaty-data-and-time-rules .chaty-date-time-option").removeClass("last");
    jQuery(".chaty-data-and-time-rules .chaty-date-time-option:last").addClass("last");
    jQuery(".chaty-data-and-time-rules .chaty-date-time-option").removeClass("first");
    jQuery(".chaty-data-and-time-rules .chaty-date-time-option:first").addClass("first");

    if(totalDateAndTimeOptions > 0) {
        jQuery(".ui-timepicker-input").timepicker({
            showLeadingZero: true
        });
        if(totalDateAndTimeOptions >= 1) {
            jQuery(".chaty-option-box .chaty-date-time-option:last").addClass("last");
        }
    }

    jQuery(document).on("change", "#chaty_attention_effect", function(){
        var currentClass = jQuery(this).attr("data-effect");
        if(currentClass != "") {
            jQuery("#iconWidget").removeClass("chaty-animation-"+currentClass);
        }
        jQuery("#iconWidget").removeClass("start-now");
        jQuery("#iconWidget").addClass("chaty-animation-"+jQuery(this).val()).addClass("start-now");
        jQuery(this).attr("data-effect", jQuery(this).val());
    });

    setInterval(function(){
        var currentClass = jQuery("#chaty_attention_effect").attr("data-effect");
        if(currentClass != "") {
            jQuery("#iconWidget").removeClass("chaty-animation-"+currentClass);
            jQuery("#iconWidget").removeClass("start-now");
            setTimeout(function(){
                jQuery("#iconWidget").addClass("chaty-animation-"+jQuery("#chaty_attention_effect").val()).addClass("start-now");
            }, 1000);
        } else {
            jQuery("#chaty_attention_effect").attr("data-effect", jQuery("#chaty_attention_effect").val());
        }
    }, 5000);

    jQuery(document).on("click", ".remove-chaty", function () {
        const $parent  = jQuery(this).parents('.chaty-option-box');
        jQuery(this).parents(".chaty-page-option").remove();
        $parent.find(".chaty-page-options .chaty-page-option").removeClass("last");
        $parent.find(".chaty-page-options .chaty-page-option:last").addClass("last");

        const status = $parent.find('.chaty-page-option').length > 0;
        if(status) {
            $parent.addClass('show-remove-rules-btn');
        } else {
            $parent.removeClass('show-remove-rules-btn');
        }
    });

    jQuery(document).on("click", "#remove-page-rules", function(e){
        e.preventDefault();
        jQuery("#chaty-page-options .chaty-page-option").remove();
        jQuery(".chaty-option-box").removeClass('show-remove-rules-btn');
    });

    jQuery(document).on("click", ".remove-day-time-rules", function(e){
        e.preventDefault();
        const $parent   = jQuery(this).parents(".chaty-option-box");
        const $scope    = $parent.find('.chaty-date-time-option');
        wp.hooks.doAction('chaty.days_and_hours_remove_rule', $scope, 'all');
        $scope.remove();
        $parent.removeClass('show-remove-rules-btn');
        // bring content into viewport 
        $parent[0].scrollIntoView({
            behavior: "smooth",
            block: "center"
        })
        
    });

    jQuery(document).on("click", ".remove-page-option", function () {
        const $scope = jQuery(this).closest(".chaty-date-time-option");
        wp.hooks.doAction('chaty.days_and_hours_remove_rule', $scope, 'single')
        jQuery(this).closest(".chaty-date-time-option").remove();
    });

    jQuery("#image-upload-content .custom-control-label").on("click", function (e) {
        e.stopPropagation();
        jQuery(this).closest(".custom-control").find("input[type=radio]").prop("checked", true);
        jQuery('.js-widget-i').trigger("change");
        return false;
    });

    // jQuery('.chaty-color-field').spectrum({
    //     chooseText: "Submit",
    //     preferredFormat: "hex3",
    //     cancelText: "Cancel",
    //     showInput: true,
    //     showAlpha: true,
    //     move: function (color) {
    //         jQuery(this).val(color.toRgbString());
    //         jQuery("#cta-box span").css("color", jQuery("#cht_cta_text_color").val());
    //         jQuery("#cta-box span").css("background", jQuery("#cht_cta_bg_color").val());
    //         jQuery("#custom-css").html("<style>.preview .page .chaty-widget .icon:before {border-color: transparent "+jQuery('#cht_cta_bg_color').val()+" transparent transparent } .preview .page .chaty-widget[style*='left: auto;'] .icon:before {border-color: transparent transparent transparent "+jQuery('#cht_cta_bg_color').val()+"}</style>");
    //         chaty_set_bg_color();

    //     },
    //     change: function (color) {
    //         jQuery(this).val(color.toRgbString());
    //         jQuery("#cta-box span").css("color", jQuery("#cht_cta_text_color").val());
    //         jQuery("#cta-box span").css("background", jQuery("#cht_cta_bg_color").val());
    //         jQuery("#custom-css").html("<style>.preview .page .chaty-widget .icon:before {border-color: transparent "+jQuery('#cht_cta_bg_color').val()+" transparent transparent } .preview .page .chaty-widget[style*='left: auto;'] .icon:before {border-color: transparent transparent transparent "+jQuery('#cht_cta_bg_color').val()+"}</style>");
    //         chaty_set_bg_color();
    //     }
    // });

    jQuery(".chaty-color-field").on("change", function () {
        chaty_set_bg_color();
    });

    jQuery(".remove-chaty-img").on("click", function (e) {
        e.stopPropagation();
    });

    var isChatyInMobile = false;
    if (/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|ipad|iris|kindle|Android|Silk|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i.test(navigator.userAgent)
        || /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(navigator.userAgent.substr(0, 4))) {
        isChatyInMobile = true;
    }

    if(!isChatyInMobile && !jQuery("body").hasClass("theme-utouch-lite")) {
        jQuery("#channels-selected-list").sortable({
            placeholder: "ui-chaty-state-hl",
            items: "li.chaty-channel:not(#chaty-social-close)",
            handle: '.move-icon',
            start: function() {
                jQuery("body").addClass("hide-agents");
            },
            stop: function () {
                jQuery("body").removeClass("hide-agents");
                set_wp_editor();
            },
            update: function (event, ui) {
                set_social_channel_order();
                change_custom_preview();
                set_wp_editor();
            }
        });
    }

    jQuery(document).ready(function(){
        set_wp_editor();

        if(jQuery("select#cht_widget_language").length) {
            jQuery(".page-url-title").text(jQuery("select#cht_widget_language option:selected").data("url"));
        }

        if(jQuery(".toast-message").length) {
            jQuery(".toast-message").addClass("active");

            setTimeout(function(){
                jQuery(".toast-message").removeClass("active");
            }, 5000);
        }

        jQuery(document).on("click", ".toast-close-btn a", function(e){
            e.preventDefault();
            jQuery(".toast-message").removeClass("active");
        });

        jQuery("input[name='switchPreview']:checked").trigger("change");
    });

    function set_wp_editor() {
        if(jQuery(".chaty-whatsapp-setting-textarea").length) {
            jQuery(".chaty-whatsapp-setting-textarea").each(function(){
                if(jQuery("#cht_social_embedded_message_"+jQuery(this).data("id")+"_ifr").length) {
                    tinymce.get(jQuery(this).attr("id")).remove();
                }
                var editorId = jQuery(this).attr("id");
                tinymce.init({
                    selector: jQuery(this).attr("id"),
                    toolbar: 'bold, italic, underline | emoji',
                    menubar: false,
                    branding: false,
                    setup: function(editor) {
                        if(editor.id != "textblock_editor" && editor.id != "custom_textblock") {
                            editor.addButton('emoji', {
                                image: cht_settings.icon_img,
                                onclick: insertEmoji,
                                classes: 'emoji-custom-icon'
                            });
                        }

                        editor.on('keyup', function (e){
                            change_custom_preview();
                            setWhatsAppPopupContent(editor.getContent());
                        });

                        function insertEmoji() {
                            const { createPopup } = window.picmoPopup;
                            const trigger = jQuery(".mce-emoji-custom-icon button").attr("id");
                            const trig = document.querySelector("#"+trigger);

                            const picker = createPopup({}, {
                                referenceElement: trig,
                                triggerElement: trig,
                                position: 'right-start',
                                hideOnEmojiSelect: false
                            });

                            picker.toggle();

                            picker.addEventListener('emoji:select', (selection) => {
                                let editor = tinyMCE.get(editorId);
                                if(!editor.selection.getNode() || editor.selection.getNode() === editor.getBody()) {
                                    editor.focus();
                                    // Move the cursor to the end of the content
                                    editor.selection.select(editor.getBody(), true);
                                    editor.selection.collapse(false);
                                    tinymce.activeEditor.execCommand('mceInsertContent', false, selection.emoji);
                                } else {
                                    tinymce.activeEditor.execCommand('mceInsertContent', false, selection.emoji);
                                }
                                change_custom_preview();
                                setWhatsAppPopupContent(editor.getContent());
                            });
                        }
                    }
                });
                tinymce.execCommand( 'mceAddEditor', true, jQuery(this).attr("id"));
            });
        }
    }

    jQuery(".close-button-img img, .close-button-img .image-upload").on("click", function () {
        var image = wp.media({
            title: 'Upload Image',
            // mutiple: true if you want to upload multiple files at once
            multiple: false,
            library: {
                type: 'image',
            }
        }).open()
            .on('select', function (e) {
                var uploaded_image = image.state().get('selection').first();
                imageData = uploaded_image.toJSON();
                jQuery('.close-button-img').addClass("active");
                jQuery('.close-button-img input').val(imageData.id);
                jQuery('.close-button-img img').attr("src", imageData.url);
                change_custom_preview();
            });
    });

    jQuery(".remove-close-img").on("click", function () {
        default_image = jQuery("#default_image").val();
        jQuery('.close-button-img').removeClass("active");
        jQuery('.close-button-img input').val("");
        jQuery('.close-button-img img').attr("src", default_image);
        change_custom_preview();
    });

    jQuery(document).on("click", ".chaty-widget.click", function(e){
        e.preventDefault();
        // jQuery(".chaty-channels").toggle();
        jQuery(".chaty-widget").toggleClass("active");
    });

    jQuery(document).on('change', '#chaty-page-options .url-options', function (ev) {
        thisVal = jQuery(this).val();
        siteURL = jQuery("#chaty_site_url").val();
        newURL = siteURL;
        jQuery(this).closest(".url-content").find(".url-title").hide();
        jQuery(this).closest(".url-content").find(".url-title").removeClass("active");
        jQuery(this).closest(".url-content").find(".chaty-url").show();
        jQuery(this).closest(".url-content").find(".url-setting-option").removeClass("active");
        jQuery(this).closest(".url-content").find(".url-default").addClass("active");
        jQuery(this).closest(".url-content").find(".items-center").addClass("active");
        if (thisVal == "home") {
            newURL = siteURL;
            jQuery(this).closest(".url-content").find(".url-default").removeClass("active");
            jQuery(this).closest(".url-content").find(".items-center").removeClass("active");
        } else if (thisVal == "page_has_url") {
            newURL = siteURL;
        } else if (thisVal == "page_contains") {
            newURL = siteURL;
        } else if (thisVal == "page_start_with") {
            newURL = siteURL;
        } else if (thisVal == "page_end_with") {
            newURL = siteURL;
        } else {
            jQuery(this).closest(".url-content").find(".url-title").hide();
            jQuery(this).closest(".url-content").find(".chaty-"+thisVal).show();
            jQuery(this).closest(".url-content").find(".url-setting-option").removeClass("active");
            jQuery(this).closest(".url-content").find("."+thisVal+"-option").addClass("active");
        }
        //jQuery(this).closest(".url-content").find(".chaty-url").text(newURL);
    });

    jQuery(".chaty-settings.cls-btn a, .close-btn-set").on("click", function (e) {
        e.preventDefault();
        jQuery(".cls-btn-settings, .close-btn-set").toggleClass("active");
    });

    /*Default Values*/
    if (jQuery("input[name='cht_position']:checked").length == 0) {
        jQuery("#right-position").prop("checked", true);
        jQuery("input[name='cht_position']:checked").trigger("change");
    }
    if (jQuery("input[name='widget_icon']:checked").length == 0) {
        jQuery("input[name='widget_icon']:first").prop("checked", true);
        jQuery("input[name='widget_icon']:checked").trigger("change");
    }
    change_custom_preview();

});

var selectedsocialSlug = "";

function upload_chaty_image(socialSlug) {
    selectedsocialSlug = socialSlug;
    var image = wp.media({
        title: 'Upload Image',
        // mutiple: true if you want to upload multiple files at once
        multiple: false,
        library: {
            type: 'image',
        }
    }).open()
        .on('select', function (e) {
            var uploaded_image = image.state().get('selection').first();
            imageData = uploaded_image.toJSON();
            jQuery('#cht_social_image_' + selectedsocialSlug).val(imageData.id);
            jQuery('.custom-image-' + selectedsocialSlug + " img").attr("src", imageData.url);
            jQuery("#chaty_image_" + selectedsocialSlug).addClass("img-active").removeClass("icon-active");
            jQuery("#chaty_image_"+selectedsocialSlug+ " .fa-icon").val("");
            change_custom_preview();
        });
}

function upload_chaty_agent_image(socialSlug) {
    selectedsocialSlug = socialSlug;
    var image = wp.media({
        title: 'Upload Image',
        // mutiple: true if you want to upload multiple files at once
        multiple: false,
        library: {
            type: 'image',
        }
    }).open()
        .on('select', function (e) {
            var uploaded_image = image.state().get('selection').first();
            imageData = uploaded_image.toJSON();
            if(jQuery('#cht_social_agent_image_' + selectedsocialSlug).length) {
                jQuery('#image_agent_data_' + selectedsocialSlug + " img.agent-image").attr("src", imageData.url);
                jQuery("#image_agent_data_" + selectedsocialSlug).addClass("img-active").removeClass("icon-active");
                jQuery("#image_agent_data_"+selectedsocialSlug+" .fa-icon").val("");
                jQuery('#image_agent_data_' + selectedsocialSlug + " input.image-id").val(imageData.id)
                change_custom_preview();
            }
        });
}

function toggle_chaty_setting(socId) {
    jQuery("#chaty-social-" + socId).find(".chaty-advance-settings").toggle();
    jQuery("#chaty-social-" + socId).find(".chaty-advance-settings").toggleClass('active');
    if(socId == "Contact_Us") {
        if(jQuery("#Contact_Us-close-btn").length) {
            var nonce = jQuery("#Contact_Us-close-btn").data("nonce");
            if (!jQuery("#Contact_Us-close-btn").hasClass("active")) {
                jQuery("#Contact_Us-close-btn").addClass("active")
                jQuery.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        "nonce": nonce,
                        "action": 'update_channel_setting'
                    },
                    success: function (response) {

                    }
                });
            }
        }
    }
    if(jQuery("#chaty-social-" + socId+ " .chaty-advance-settings.active").length) {
        // jQuery("body,html").animate({
        //     scrollTop: jQuery("#chaty-social-" + socId+ " .chaty-advance-settings.active").offset().top - 50
        // }, 500);
    }
    change_custom_preview();
}

function chaty_set_bg_color() {
    jQuery(".chaty-color-field:not(.button-color)").each(function () {
        if (jQuery(this).val() != "" && jQuery(this).val() != "#ffffff") {
            if(jQuery(this).hasClass("agent-bg-color")) {
                defaultColor = jQuery(this).val();
                jQuery(this).closest(".chaty-agent-form").find(".chaty-agent-setting").find(".color-element").css("fill", defaultColor);
                jQuery(this).closest(".chaty-agent-form").find(".chaty-agent-setting").find(".custom-agent-image").css("background", defaultColor);
                jQuery(this).closest(".chaty-agent-form").find(".chaty-agent-setting").find(".facustom-icon").css("background", defaultColor);
            } else if(jQuery(this).hasClass("agent-icon-color")) {
                defaultColor = jQuery(this).val();
                jQuery(this).closest("li.agent-info").find(".color-element").css("fill", defaultColor);
                jQuery(this).closest("li.agent-info").find(".custom-chaty-image").css("background", defaultColor);
                jQuery(this).closest("li.agent-info").find(".facustom-icon").css("background", defaultColor);
            } else if (jQuery(this).closest("li").data("id") != "Linkedin" || (jQuery(this).closest("li").data("id") == "Linkedin" && jQuery(this).val() != "#ffffff")) {
                defaultColor = jQuery(this).val();
                jQuery(this).closest(".channels-selected__item").find(".color-element").css("fill", defaultColor);
                jQuery(this).closest(".channels-selected__item").find(".custom-chaty-image").css("background", defaultColor);
                jQuery(this).closest(".channels-selected__item").find(".facustom-icon").css("background", defaultColor);
            }
        }
    });
    change_custom_preview();
}
var activeWeChatChannel = '';
function upload_qr_code(channel_name) {
    activeWeChatChannel = channel_name;
    var image = wp.media({
        title: 'Upload QR Image',
        multiple: false,
        library: {
            type: 'image'
        }
    }).open()
        .on('select', function (e) {
            var uploaded_image = image.state().get('selection').first();
            imageData = uploaded_image.toJSON();
            jQuery('#upload_qr_code_val-'+activeWeChatChannel).val(imageData.id);
            jQuery("."+activeWeChatChannel+"-qr-code-setting").addClass("active");
            jQuery("#"+activeWeChatChannel+"-qr-code-image").html("<img src='"+imageData.url+"' alt=''>");
            change_custom_preview();
        });
}

function remove_qr_code(channel_name) {
    jQuery('#upload_qr_code_val-'+channel_name).val("");
    jQuery("."+channel_name+"-qr-code-setting").removeClass("active");
    jQuery("#"+channel_name+"-qr-code-image").html("");
    change_custom_preview();
}

function remove_chaty_image(socId) {
    default_image = jQuery("#default_image").val();
    jQuery('#cht_social_image_' + socId).val("");
    jQuery('#cht_social_image_src_' + socId).attr("src", default_image);
    jQuery("#chaty_image_"+socId).removeClass("icon-active").removeClass("img-active");
    jQuery("#chaty_image_"+socId+ " .fa-icon").val("");
    change_custom_preview();
}

var baseIcon = '<svg version="1.1" id="ch" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="-496 507.7 54 54" style="enable-background:new -496 507.7 54 54;" xml:space="preserve">\n' +
        '                            <style type="text/css">.st0 {fill: #A886CD;}  .st1 {fill: #FFFFFF;}\n' +
        '                        </style><g><circle class="st0" cx="-469" cy="534.7" r="27"/></g><path class="st1" d="M-459.9,523.7h-20.3c-1.9,0-3.4,1.5-3.4,3.4v15.3c0,1.9,1.5,3.4,3.4,3.4h11.4l5.9,4.9c0.2,0.2,0.3,0.2,0.5,0.2 h0.3c0.3-0.2,0.5-0.5,0.5-0.8v-4.2h1.7c1.9,0,3.4-1.5,3.4-3.4v-15.3C-456.5,525.2-458,523.7-459.9,523.7z"/>\n' +
        '                                                    <path class="st0" d="M-477.7,530.5h11.9c0.5,0,0.8,0.4,0.8,0.8l0,0c0,0.5-0.4,0.8-0.8,0.8h-11.9c-0.5,0-0.8-0.4-0.8-0.8l0,0\n' +
        '                            C-478.6,530.8-478.2,530.5-477.7,530.5z"/>\n' +
        '                                                    <path class="st0" d="M-477.7,533.5h7.9c0.5,0,0.8,0.4,0.8,0.8l0,0c0,0.5-0.4,0.8-0.8,0.8h-7.9c-0.5,0-0.8-0.4-0.8-0.8l0,0\n' +
        '                            C-478.6,533.9-478.2,533.5-477.7,533.5z"/>\n' +
        '                        </svg>',
    defaultIcon = '<svg version="1.1" id="ch" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="-496 507.7 54 54" style="enable-background:new -496 507.7 54 54;" xml:space="preserve">\n' +
        '                            <style type="text/css">.st0 {fill: #A886CD;}  .st1 {fill: #FFFFFF;}\n' +
        '                        </style><g><circle class="st0" cx="-469" cy="534.7" r="27"/></g><path class="st1" d="M-459.9,523.7h-20.3c-1.9,0-3.4,1.5-3.4,3.4v15.3c0,1.9,1.5,3.4,3.4,3.4h11.4l5.9,4.9c0.2,0.2,0.3,0.2,0.5,0.2 h0.3c0.3-0.2,0.5-0.5,0.5-0.8v-4.2h1.7c1.9,0,3.4-1.5,3.4-3.4v-15.3C-456.5,525.2-458,523.7-459.9,523.7z"/>\n' +
        '                                                    <path class="st0" d="M-477.7,530.5h11.9c0.5,0,0.8,0.4,0.8,0.8l0,0c0,0.5-0.4,0.8-0.8,0.8h-11.9c-0.5,0-0.8-0.4-0.8-0.8l0,0\n' +
        '                            C-478.6,530.8-478.2,530.5-477.7,530.5z"/>\n' +
        '                                                    <path class="st0" d="M-477.7,533.5h7.9c0.5,0,0.8,0.4,0.8,0.8l0,0c0,0.5-0.4,0.8-0.8,0.8h-7.9c-0.5,0-0.8-0.4-0.8-0.8l0,0\n' +
        '                            C-478.6,533.9-478.2,533.5-477.7,533.5z"/>\n' +
        '                        </svg>'
var iconBlock = document.getElementById('iconWidget');

function set_social_channel_order() {
    socialString = [];
    jQuery("#channels-selected-list > li.chaty-channel").each(function () {
        socialString.push(jQuery(this).attr("data-id"));
    });
    socialString = socialString.join(",");
    jQuery("#cht_numb_slug").val(socialString);
}

function save_contact_form_field_order() {
    var fieldValue = [];
    jQuery("#chaty-social-Contact_Us .form-field-setting-col .field-setting-col").each(function (){
        fieldValue.push(jQuery(this).attr("data-order"));
    });
    fieldValue = fieldValue.join(",");
    jQuery("#contact_form_field_order").val(fieldValue);
}

/*
Date: 2021-08-04 Preview Code
*/

(function (factory) {
    "use strict";
    if (typeof define === 'function' && define.amd) {
        define(['jquery'], factory);
    }
    else if(typeof module !== 'undefined' && module.exports) {
        module.exports = factory(require('jquery'));
    }
    else {
        factory(jQuery);
    }
}(function ($, undefined) {


    var chtIframeData = "";
    $(document).ready(function() {
        $(document).on("keyup", ".select2-search__field", function(){
            if($(this).val() == "") {
                if($(this).closest(".url-setting-option").length) {
                    var eleId = $(this).closest(".url-setting-option").find("select").attr("id");
                    if($("#"+eleId).length) {
                        $('#'+eleId).select2('close');
                    }
                }
            }
        });

        $(document).on("click", "#image-upload-content .custom-control-radio", function(e){
            e.preventDefault();
            $(".js-upload").prop("checked", true);
            change_custom_preview();
        });

        if(jQuery("#wp-cta_wc_body_text-wrap").length) {
            var iframeData = jQuery("#wp-cta_wc_body_text-wrap").find("iframe");
            iframeData.contents().find("head").append("<style>p{margin:0; padding:0 }body{padding:10px !important;}</style>");
        }


        $(document).on("click", ".btn-cancel", function(e){
            e.preventDefault();
            var socialChannel = $(this).data("social");
            $(this).closest("li.chaty-channel").remove();
            $(".chat-channel-"+socialChannel).removeClass("active");
            change_custom_preview();
            // call when any channel is removed or updated
            const channel_list4 = [];
            jQuery('.channels-icons > .icon.active').each( (i, item) => {
                channel_list4.push( item.dataset.social );
            } )
            wp.hooks.doAction('chaty.channel_update', {
                channel     : channel_list4,         // active channel list
                target      : socialChannel,               // channel that removed last
                action      : 'removed',            // added || removed,
                isExceeded  : false,
            }); 
        });

        $(document).on("click", ".js-switch-preview", function(){
            change_custom_preview();
        });

        $(document).on("click", ".channels__view-check, #cht_close_button", function(){
            change_custom_preview();
        });

        $(document).on("change", "input[name='cht_position']:checked", function(){
            if($(this).val() == "custom") {
                $("#positionPro").show();
            } else {
                $("#positionPro").hide();
            }
            change_custom_preview();
        });

        $(document).on("click", "#aim-modal", function(){
            $(".aim-cancel-icon-button").trigger("click");
        });

        $(document).on("click", ".aim-modal--content", function(e){
            e.stopPropagation()
        });

        $(document).on("click", "#cht_pending_messages", function(){
            if($(this).is(":checked")) {
                $(".pending-message-items").addClass("active");
            } else {
                $(".pending-message-items").removeClass("active");
            }
            change_custom_preview();
        });

        $(document).on("change", "input[name='positionSide']:checked, input[name='cht_cta_action']:checked, input[name='widget_icon']:checked", function(){
            change_custom_preview();
        });

        $(document).on("change", "#chaty_attention_effect, #cht_number_of_messages, #chaty_icons_view", function(){
            change_custom_preview();
        });

        $(document).on("change", ".form-fonts", function(){
            jQuery("#custom-font-style, .preview-google-font").remove();
            var fontFamily = jQuery(".form-fonts").val();
            if(fontFamily != "") {
                if (fontFamily == "System Stack") {
                    fontFamily = "-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Oxygen-Sans,Ubuntu,Cantarell,Helvetica Neue,sans-serif";
                }
                jQuery("head").append("<style id='custom-font-style'></style>");
                customCSS = ".chaty-preview, .chaty-preview *, .chaty-preview *:after { font-family: "+fontFamily+" }";
                jQuery("#custom-font-style").html(customCSS);

                if(jQuery(".form-fonts option:selected").closest("optgroup").attr("label") != "Default") {
                    jQuery("head").append('<link class="preview-google-font" rel="preconnect" href="https://fonts.googleapis.com">' +
                        '<link class="preview-google-font" rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' +
                        '<link class="preview-google-font" href="https://fonts.googleapis.com/css2?family='+fontFamily+'&display=swap" rel="stylesheet">');
                }
            }
        });

        $(document).on("change", "#chaty_default_state", function(){
            if($(this).val() == "open") {
                $(".hide-show-button").addClass("active");
            } else {
                $(".hide-show-button").removeClass("active");
            }
            change_custom_preview();
        });

        $(document).on("change", "input[name='cht_color']:checked", function(){
            change_custom_preview();
        });

        $(document).on("click", ".chaty-preview.click:not(.single) .chaty-preview-cta, .chaty-preview.open:not(.single) .chaty-preview-cta", function(){
            $(".chaty-preview").toggleClass("active");
        });

        $(document).on("mouseover", ".chaty-preview.hover:not(.single):not(.on) .chaty-preview-cta", function(){
            $(".chaty-preview").addClass("active");
            $(".chaty-preview").addClass("on");
        });

        $(document).on("click", ".chaty-preview.hover:not(.single) .chaty-preview-cta", function(){
            $(".chaty-preview").toggleClass("active");
        });

        $(document).on("mouseleave", ".chaty-preview.hover:not(.single) .chaty-preview-cta", function(){
            $(".chaty-preview").removeClass("on");
        });

        $(document).on("keyup", ".test_textarea, .chaty-title", function(){
            change_custom_preview();
        });

        // $(document).on("keyup", ".custom-textarea textarea", function (){
        //     change_custom_preview();
        // });

        $(document).on("click", ".trigger-block input[type='checkbox']", function(){
            if($(this).is(":checked")) {
                $(this).closest(".trigger-option-block").find("input[type='number']").prop("disabled", false);
                $(this).closest(".trigger-option-block").find("input[type='text']").prop("disabled", false);
            } else {
                $(this).closest(".trigger-option-block").find("input[type='number']").prop("disabled", true);
                $(this).closest(".trigger-option-block").find("input[type='text']").prop("disabled", true);
            }
        });

        if(!$("#trigger_on_time").is(":checked")) {
            $("#chaty_trigger_time").prop("disabled", true);
        }
        if(!$("#chaty_trigger_hide").is(":checked")) {
            $("#chaty_trigger_hide_time").prop("disabled", true);
        }
        if(!$("#chaty_trigger_on_scroll").is(":checked")) {
            $("#chaty_trigger_on_page_scroll").prop("disabled", true);
        }

        jQuery(".form-fonts").trigger("change");

        setCtaEditor();
    });

    function setCtaEditor() {
        if(jQuery(".chaty-cta-body-textarea").length) {
            if(jQuery("#cta_body_text_ifr").length) {
                tinymce.get('cta_body_text').remove();
            }
            tinymce.init({
                selector: 'cta_body_text',
                toolbar: 'bold, italic, underline | emoji',
                menubar: false,
                branding: false,
                setup: function(editor) {
                    if(editor.id != "textblock_editor" && editor.id != "custom_textblock") {
                        editor.addButton('emoji', {
                            image: cht_settings.icon_img,
                            onclick: insertEmoji,
                            classes: 'cta-emoji-custom-icon'
                        });
                    }

                    editor.on('keyup', function (e){
                        bodyMsg = editor.getContent();
                        change_custom_preview();
                    });

                    function insertEmoji() {
                        const { createPopup } = window.picmoPopup;
                        const trigger = jQuery(".mce-cta-emoji-custom-icon button").attr("id");
                        const trig = document.querySelector("#"+trigger);

                        const picker = createPopup({}, {
                            referenceElement: trig,
                            triggerElement: trig,
                            position: 'right-start',
                            hideOnEmojiSelect: false
                        });

                        picker.toggle();

                        picker.addEventListener('emoji:select', (selection) => {
                            let editor = tinyMCE.get('cta_body_text');
                            if(!editor.selection.getNode() || editor.selection.getNode() === editor.getBody()) {
                                editor.focus();
                                // Move the cursor to the end of the content
                                editor.selection.select(editor.getBody(), true);
                                editor.selection.collapse(false);
                                tinymce.activeEditor.execCommand('mceInsertContent', false, selection.emoji);
                            } else {
                                tinymce.activeEditor.execCommand('mceInsertContent', false, selection.emoji);
                            }
                            bodyMsg = editor.getContent();
                            change_custom_preview();
                        });
                    }
                }
            });
            tinymce.execCommand( 'mceAddEditor', true, 'cta_body_text');
        }
    }
}));

var imageDataEvent = false;
function loadPreviewFile(event) {
    imageDataEvent = event;
    jQuery(event.target).parents('#image-upload-content').find('#uploadInput').prop('checked', true)
    if(jQuery("#testUpload").val() != "") {
        var output = document.getElementById('outputImage');
        output.src = URL.createObjectURL(event.target.files[0]);
        output.onload = function () {
            URL.revokeObjectURL(output.src) // free memory
            jQuery("#image-upload").addClass("has-custom-image");
            change_custom_preview();
        }
    }
}


/*
Date: 2021-08-04 Chaty in Steps
*/

(function (factory) {
    "use strict";
    if (typeof define === 'function' && define.amd) {
        define(['jquery'], factory);
    }
    else if(typeof module !== 'undefined' && module.exports) {
        module.exports = factory(require('jquery'));
    }
    else {
        factory(jQuery);
    }
}(function ($, undefined) {
    $(document).ready(function(){

        $(document).on("click", "#chaty-social-channel", function(){
            $("#chaty-social-channel, #chaty-app-customize-widget, #chaty-triger-targeting").removeClass("completed");
            $("#chaty-social-channel, #chaty-app-customize-widget, #chaty-triger-targeting").removeClass("active");
            $(this).addClass("active");
            $(".social-channel-tabs").removeClass("active");
            $("#chaty-tab-social-channel").addClass("active");
            $("#current_step").val(1);
            $(".footer-buttons").removeClass("step-3").removeClass("step-2").addClass("step-1");
        });

        $(document).on("click", "#chaty-app-customize-widget", function(){
            $(".footer-buttons").removeClass("step-3").removeClass("step-1").addClass("step-2");
            checkForFirstStep();
        });

        $(document).on("click", "#chaty-triger-targeting", function(){
            $("#chaty-triger-targeting").removeClass("completed");
            $("#chaty-social-channel, #chaty-app-customize-widget, #chaty-triger-targeting").removeClass("active");
            $(this).addClass("active");
            $("#chaty-social-channel, #chaty-app-customize-widget").addClass("completed");
            $(".social-channel-tabs").removeClass("active");
            $("#chaty-tab-triger-targeting").addClass("active");

            $(".footer-buttons").removeClass("step-1").removeClass("step-2").addClass("step-3");
            $("#current_step").val(3);
        });

        $(document).on("click", "#next-button", function(e){
            e.preventDefault();
            if($("#chaty-social-channel").hasClass("active")) {
                $("#chaty-app-customize-widget").trigger("click");
            } else if($("#chaty-app-customize-widget").hasClass("active")) {
                $("#chaty-triger-targeting").trigger("click");
            }
        });

        $(document).on("click", "#back-button", function(e){
            e.preventDefault();
            if($("#chaty-tab-triger-targeting").hasClass("active")) {
                $("#chaty-triger-targeting").removeClass("completed");
                $("#chaty-social-channel, #chaty-app-customize-widget, #chaty-triger-targeting").removeClass("active");
                $("#chaty-app-customize-widget").addClass("active");
                $("#chaty-social-channel").addClass("completed");
                $(".social-channel-tabs").removeClass("active");
                $("#chaty-tab-customize-widget").addClass("active");
                $(".footer-buttons").removeClass("step-1").removeClass("step-3").addClass("step-2");
                $("#current_step").val(2);
            } else if($("#chaty-app-customize-widget").hasClass("active")) {
                $("#chaty-triger-targeting, #chaty-app-customize-widget").removeClass("completed");
                $(".social-channel-tabs").removeClass("active");
                $("#chaty-social-channel, #chaty-app-customize-widget, #chaty-triger-targeting").removeClass("active");
                $("#chaty-social-channel").addClass("active");
                $("#chaty-tab-social-channel").addClass("active");
                $(".footer-buttons").removeClass("step-2").removeClass("step-3").addClass("step-1");
                $("#current_step").val(1);
            }
        });
    });


    function checkForFirstStep() {
        $(".chaty-popup").hide();
        if($("#cht-form .js-chanel-desktop").length == 0 || $("#cht-form .js-chanel-mobile").length == 0) {
            $("#no-step-device-popup").show();
            return;
        } else if($("#cht-form .js-chanel-desktop:checked").length == 0 && $("#cht-form .js-chanel-mobile:checked").length == 0) {
            $("#device-step-popup").show();
            return;
        }
        $("#chaty-app-customize-widget, #chaty-triger-targeting").removeClass("completed");
        $("#chaty-social-channel, #chaty-app-customize-widget, #chaty-triger-targeting").removeClass("active");
        $("#chaty-app-customize-widget").addClass("active");
        $("#chaty-social-channel").addClass("completed");
        $(".social-channel-tabs").removeClass("active");
        $("#chaty-tab-customize-widget").addClass("active");
        $(".footer-buttons").removeClass("step-1").removeClass("step-3").addClass("step-2");
        $("#current_step").val(2);
    }

}));

/*
Date: 2021-08-04 Agent Functionality
*/

(function (factory) {
    "use strict";
    if (typeof define === 'function' && define.amd) {
        define(['jquery'], factory);
    }
    else if(typeof module !== 'undefined' && module.exports) {
        module.exports = factory(require('jquery'));
    }
    else {
        factory(jQuery);
    }
}(function ($, undefined) {
    var selectedChannel = "";
    $(document).ready(function(){
        /* AGENT FUNCTIONALITY */
        $(document).on("click", ".customize-agent-button", function(){
            if($(this).closest("li.chaty-channel").find(".agent-info").length == 0) {
                $(this).closest("li.chaty-channel").find(".add-agent-button button").trigger("click");
            }
            change_custom_preview();
        });

        $(document).on("click", ".remove-agent-btn", function(){
            if($(this).closest("ul.agent-list").find("li.agent-info").length == 1) {
                $(this).closest("li.chaty-channel").removeClass("has-agent-view");
                $(this).closest("li.chaty-channel").find(".is-agent-active").val(0);
                $(this).closest("li.chaty-channel").find(".chaty-advance-settings").show();
                $(this).closest("li.chaty-channel").find(".chaty-advance-settings").addClass("active");
            }
            $(this).closest("li.agent-info").remove();
            change_custom_preview();
        });

        $(document).on("click", ".remove-img-icon", function(){
            var thisSlug = $(this).data("slug");
            if($("#image_agent_data_"+thisSlug).length) {
                $("#image_agent_data_"+thisSlug).removeClass("img-active").removeClass("icon-active");
                $("#image_agent_data_"+thisSlug+" .image-id").val("");
                $("#image_agent_data_"+thisSlug+" .fa-icon").val("");
                change_custom_preview();
            }
        });

        $(document).on("click", ".agent-button-action", function(e){
            e.preventDefault();
            $(this).closest("li.chaty-channel").find(".is-agent-active").val(1);
            $(this).closest("li.chaty-channel").addClass("has-agent-view");

            var currentVal = $(this).closest("li.chaty-channel").find(".chaty-default-settings").find(".channels__input").val();

            if($(this).closest("li.chaty-channel").find(".agent-info").length == 0) {
                $(this).closest("li.chaty-channel").find(".add-agent-button button").trigger("click");
                $(this).closest("li.chaty-channel").find(".agent-info").find(".agent-input-value").val(currentVal).addClass("whatsapp-agent-input-deafault-value");
                var thisCount = $(this).closest("li.chaty-channel").find(".agent-info").find(".agent-input-value").data("id");
                if ($("#cht_social_agent_Whatsapp_" + thisCount).length) {
                    cht_settings.channel_settings['Whatsapp_agent_'+thisCount] = document.querySelector("#cht_social_agent_Whatsapp_" + thisCount);
                    cht_settings.channel_settings['Whatsapp_agent_Country_'+thisCount] = window.intlTelInput(cht_settings.channel_settings['Whatsapp_agent_'+thisCount], {
                        formatOnDisplay: false,
                        hiddenInput: 'full_number',
                        initialCountry: 'auto',
                        nationalMode: false,
                        autoHideDialCode: false,
                        utilsScript: cht_settings.plugin_url + "admin/assets/js/utils.js",
                    });
                    setTimeout(function () {
                        $(".custom-agent-channel-Whatsapp").trigger("keyup");
                    }, 200);
                }
                if($(this).closest("li.chaty-channel").find(".agent-info").find(".agent-input-value").val() != "") {
                    $(this).closest("li.chaty-channel").find(".agent-info").find(".agent-input-value + .wf-test-button").addClass("active");
                }
                var preset_msg = $(this).closest("li.chaty-channel").find(".chaty-advance-settings").find(".pre-set-message-whatsapp").val();
                $(this).closest("li.chaty-channel").find(".agent-info:first-child .agent-channel-setting-advance").find(".pre-set-message-whatsapp").val(preset_msg);
                // var wp_bg_color = $(this).closest("li.chaty-channel").find(".chaty-advance-settings").find(".chaty-bg-color").val();
                // $(this).closest("li.chaty-channel").find(".agent-info:first-child .agent-channel-setting-advance").find(".agent-icon-color").val(wp_bg_color);
                // if($(this).closest("li.chaty-channel").find(".chaty-default-settings #chaty_image_Whatsapp").hasClass("img-active")) {
                //     var img_path = $(this).closest("li.chaty-channel").find(".chaty-default-settings #chaty_image_Whatsapp.img-active #image_data_Whatsapp img").attr("src");
                //     $(this).closest("li.chaty-channel").find(".chaty-agent-advance-setting .agent-list .agent-info:first-child .agent-channel-setting .agent-icon").addClass("img-active");
                //     $(this).closest("li.chaty-channel").find(".chaty-agent-advance-setting .agent-list .agent-info:first-child .agent-channel-setting .agent-icon").find(".custom-agent-image img").attr("src", img_path);
                //     var img_id = $(this).closest("li.chaty-channel").find(".chaty-advance-settings #cht_social_image_Whatsapp").val();
                //     $(this).closest("li.chaty-channel").find(".chaty-agent-advance-setting .agent-list .agent-info:first-child .agent-channel-setting .agent-icon").find(".custom-agent-image .image-id").val(img_id);
                // } else if($(this).closest("li.chaty-channel").find(".chaty-default-settings #chaty_image_Whatsapp").hasClass("icon-active")) {
                //     var agent_icon = $(this).closest("li.chaty-channel").find(".chaty-default-settings #chaty_image_Whatsapp.icon-active .facustom-icon").html();
                //     $(this).closest("li.chaty-channel").find(".chaty-agent-advance-setting .agent-list .agent-info:first-child .agent-channel-setting .agent-icon").addClass("icon-active");
                //     $(this).closest("li.chaty-channel").find(".chaty-agent-advance-setting .agent-list .agent-info:first-child .agent-channel-setting .agent-icon").find(".facustom-icon").html(agent_icon);
                //     var fa_class = $(this).closest("li.chaty-channel").find(".chaty-default-settings #chaty_image_Whatsapp.icon-active .fa-icon").val();
                //     $(this).closest("li.chaty-channel").find(".chaty-agent-advance-setting .agent-list .agent-info:first-child .agent-channel-setting .agent-icon").find(".fa-icon").val(fa_class);
                // }

                // if(currentVal != "") {
                //     $(this).closest("li.chaty-channel").find(".add-agent-button button").trigger("click");
                // }
            }

            change_custom_preview();

            // $(document).trigger('chatyColorPicker/trigger', [{
            //     $scope   : $(this).parents('li.chaty-channel'),
            //     element  : '.chaty-color-field'
            // }]);

        });

        $(document).on("click", ".remove-agents-button", function(){
            selectedChannel = $(this).data("id");
            $("#remove-agents-popup").show();
        });


        $(".remove-agent-list").on("click", function(){
            $("#chaty-social-"+selectedChannel).removeClass("has-agent-view");
            $("#chaty-social-"+selectedChannel+" .is-agent-active").val(0);
            $("#remove-agents-popup").hide();
            change_custom_preview();
        });

        if($(".chaty-channel.has-agent-view .agent-list").length) {
            $(".chaty-channel.has-agent-view .agent-list").each(function(){
                var agentId = $(this).attr("id");
                $("#"+agentId).sortable({
                    placeholder: "ui-chaty-state-hl",
                    items: $("#"+agentId+" > li.agent-info"),
                    handle: '.move-channel-icon',
                    update: function (event, ui) {
                        set_agent_channel_order();
                    }
                });
            })
        }

        $(document).on("click", ".add-agent-button button", function(){
            var thisCount = parseInt($(this).closest(".chaty-agents").data("count"));
            thisCount = thisCount+1;
            var thisSlug = $(this).data("slug");
            $(this).closest(".chaty-agents").data("count", thisCount);
            var tempHtml = $(this).closest(".chaty-agent-advance-setting").find(".default-agent-setting").html();
            tempHtml = tempHtml.replace(/__count__/g,thisCount);
            tempHtml = tempHtml.replace(/chaty-color-field-agent/g,"chaty-color-field");
            tempHtml = tempHtml.replace(/icon-picker-wrap-agent/g,"icon-picker-wrap");
            tempHtml = tempHtml.replace(/chaty-whatsapp-phone-alt/g,"chaty-whatsapp-phone");
            $(this).closest(".chaty-agents").find("ul.agent-list").append("<li class='agent-info'>"+tempHtml+"</li>");

            $(this).closest(".chaty-agents").find("ul.agent-list li.agent-info:last-child .agent-input-value ").focus();
            if($('#agent-icon-picker-'+thisSlug+'-'+thisCount).length && $('#select-agent-icon-'+thisSlug+'-'+thisCount).length) {
                var newIconLib = {
                    "material":{
                        "regular":{
                            "list-icon":"",
                            "icon-style":"mt-regular",
                            "icons":["some","some2"],
                        }
                    }
                }
                AestheticIconPicker({
                    'selector': '#agent-icon-picker-' + thisSlug + '-' + thisCount, // must be an ID
                    'onClick': '#select-agent-icon-' + thisSlug + '-' + thisCount,  // must be an ID
                    "iconLibrary": newIconLib
                });
            }

            change_custom_preview();

            $(document).trigger('chatyColorPicker/trigger', [{
                $scope   : $(this).parents('.chaty-agents'),
                element  : '.chaty-color-field'
            }]);

            var agentId = "agent-list-"+jQuery(this).closest("li.chaty-channel").data("id");
            $("#"+agentId).sortable({
                placeholder: "ui-chaty-state-hl",
                items: $("#"+agentId+" > li.agent-info"),
                handle: '.move-channel-icon',
                update: function (event, ui) {
                    set_agent_channel_order();
                }
            });

            if(thisSlug == "Whatsapp") {
                setCountryForWhatsApp(thisCount);
            }

            //checkForUserCountry();
        });

        checkForUserCountry();
    });

    function set_agent_channel_order() {
        $("#channels-selected-list > .chaty-channel.has-agent-view").each(function(){
            var channelId = $(this).data("id");
            var agentOrder = "";
            $("#agent-list-"+channelId+" .agent-channel-setting").each(function(){
                agentOrder += $(this).data("item")+",";
            });
            $("#agent_order_"+channelId).val(agentOrder);
        });
        change_custom_preview();
    }
}));

function checkForUserCountry() {
    var userCountry = getUserCountry();
    if(userCountry != "") {
        setWhatsAppCountryFlag();
    }
}

function getUserCountry() {
    var clientCountry = chatyGetCookie("cht_country_code");
    if(!clientCountry) {
        setClientCountry();
    }
    return clientCountry;
}

function setCountryForWhatsApp(thisCount) {
    if (jQuery("#cht_social_agent_Whatsapp_" + thisCount).length) {
        cht_settings.channel_settings['Whatsapp_agent_'+thisCount] = document.querySelector("#cht_social_agent_Whatsapp_" + thisCount);
        cht_settings.channel_settings['Whatsapp_agent_Country_'+thisCount] = window.intlTelInput(cht_settings.channel_settings['Whatsapp_agent_'+thisCount], {
            formatOnDisplay: false,
            hiddenInput: 'full_number',
            initialCountry: 'auto',
            nationalMode: false,
            autoHideDialCode: false,
            utilsScript: cht_settings.plugin_url + "admin/assets/js/utils.js",
        });

        var user_country = getUserCountry();
        if(user_country != "-" && jQuery("#cht_social_agent_Whatsapp_"+thisCount).val() == "") {
            setTimeout(function(){
                cht_settings.channel_settings['Whatsapp_agent_Country_'+thisCount].setCountry(user_country);
                jQuery(".custom-agent-channel-Whatsapp").trigger("keyup");
            }, 200);
        } else if(jQuery("#cht_social_agent_Whatsapp_"+thisCount).val() != "") {
            setTimeout(function(){
                jQuery(".custom-agent-channel-Whatsapp").trigger("keyup");
            }, 200);
        }
    }
}

function setClientCountry() {
    var clientCountry = chatyGetCookie("cht_country_code");
    if(!clientCountry) {
        var $apiURL = 'https://www.cloudflare.com/cdn-cgi/trace';
        jQuery.get($apiURL, function (countryData) {
            if (countryData) {
                var countryCode = "-";
                var countryInfo = countryData.match("loc=(.*)");
                if (countryInfo.length > 1) {
                    countryInfo = countryInfo[1];
                    if (countryInfo) {
                        countryCode = countryInfo.toUpperCase();
                        if (countryCode == "") {
                            countryCode = "-";
                        }
                    }
                }

            }
            chatySetCookie("cht_country_code", countryCode, 30 * 24);
            setWhatsAppCountryFlag();
        });
    }
}

function setWhatsAppCountryFlag() {

    if(jQuery("#channel_input_Whatsapp").length) {
        cht_settings.channel_settings['Whatsapp'] = document.querySelector("#channel_input_Whatsapp");
        cht_settings.channel_settings['Whatsapp_Country'] = window.intlTelInput(cht_settings.channel_settings['Whatsapp'], {
            formatOnDisplay: false,
            hiddenInput: 'full_number',
            initialCountry: 'auto',
            nationalMode: false,
            autoHideDialCode: false,
            utilsScript: cht_settings.plugin_url + "admin/assets/js/utils.js",
        });


        var user_country = getUserCountry();
        if(user_country != "-" && jQuery("#channel_input_Whatsapp").val() == "") {
            setTimeout(function(){
                cht_settings.channel_settings['Whatsapp_Country'].setCountry(user_country);
                jQuery("#channel_input_Whatsapp").trigger("keyup");
            }, 200);
        } else if(jQuery("#channel_input_Whatsapp").val() != "") {
            setTimeout(function(){
                jQuery("#channel_input_Whatsapp").trigger("keyup");
            }, 200);
        }
    }

    /*if(jQuery(".custom-agent-channel-Whatsapp").length) {
        jQuery(".custom-agent-channel-Whatsapp").each(function (){
            var id = jQuery(this).data("id");
            // cht_settings.channel_settings['Whatsapp_agent'] = document.querySelector("#cht_social_agent_Whatsapp_"+id);
            // cht_settings.channel_settings['Whatsapp_agent_Country'] = window.intlTelInput(cht_settings.channel_settings['Whatsapp_agent'], {
            //     formatOnDisplay: false,
            //     hiddenInput: 'full_number',
            //     initialCountry: 'auto',
            //     nationalMode: false,
            //     autoHideDialCode: false,
            //     utilsScript: cht_settings.plugin_url + "admin/assets/js/utils.js",
            // });

            var user_country = getUserCountry();
            if(user_country != "-" && jQuery("#cht_social_agent_Whatsapp_"+id).val() == "") {
                setTimeout(function(){
                    // cht_settings.channel_settings['Whatsapp_agent_Country'].setCountry(user_country);
                    // var iti = cht_settings.channel_settings['Whatsapp_agent_Country'];
                    // var data = iti.getSelectedCountryData();
                    // jQuery("#cht_social_agent_Whatsapp_"+id).val("+" + data.dialCode);
                    // jQuery(".custom-agent-channel-Whatsapp").trigger("change");
                }, 200);
            } else if(jQuery("#cht_social_agent_Whatsapp_"+id).val() != "") {
                setTimeout(function(){
                    // jQuery(".custom-agent-channel-Whatsapp").trigger("keyup");
                }, 200);
            }
        });
    }*/

    /*if(jQuery(".custom-channel-Whatsapp:not(#channel_input_Whatsapp)").length) {
        jQuery(".custom-channel-Whatsapp:not(#channel_input_Whatsapp)").each(function(){
            var dataSlag = jQuery(this).closest("li.chaty-channel").data("id");
            if(dataSlag != undefined) {
                if(jQuery("#channel_input_"+dataSlag).length) {
                    cht_settings.channel_settings[dataSlag] = document.querySelector("#channel_input_"+dataSlag);
                    window.intlTelInput(cht_settings.channel_settings[dataSlag], {
                        dropdownContainer: document.body,
                        formatOnDisplay: true,
                        hiddenInput: "full_number",
                        initialCountry: "auto",
                        nationalMode: false,
                        utilsScript: cht_settings.plugin_url + "admin/assets/js/utils.js",
                    });
                }
            }
        });
    }*/
}


function chatySetCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + exdays * 24 * 60 * 60 * 1000);
    var expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + "; " + expires + "; path=/; SameSite=Lax";
}

function chatyGetCookie(cookieName) {
    var cookieName = cookieName + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(cookieName) == 0) {
            return c.substring(cookieName.length, c.length); // return data if cookie exists
        }
    }
    return null; // return null if cookie doesn't exists
}

function chatyCheckCookie(cookieName) {
    var cookie = chatyGetCookie(cookieName);
    if (cookie != "" && cookie !== null) {
        return true;
    } else {
        return false;
    }
}

function chatyDeleteCookie(cookieName) {
    document.cookie = cookieName + '=;expires=Thu, 01 Jan 1970 00:00:01 GMT;';
}
