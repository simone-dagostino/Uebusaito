/* global ajax, utility, popupEasy */

var controlPanelSetting = new ControlPanelSetting();

function ControlPanelSetting() {
    // Vars
    var self = this;
    
    // Properties
    
    // Functions public
    self.init = function() {
        utility.accordion();
        
        languageManage();
        
        utility.wordTag("#form_setting_roleUserId");
        
        $("#form_setting_payPalCurrencyCode").on("keyup", "", function() {
            $(this).val($(this).val().toUpperCase());
        });
        
        $("#form_cp_setting_save").on("submit", "", function(event) {
            event.preventDefault();
            
            var propNameLanguageManage = $("#form_setting_languageManage").prop("name");
            $("#form_setting_languageManage").removeAttr("name");
            var propNameLanguageManageDate = $("#form_setting_languageManageDate").prop("name");
            $("#form_setting_languageManageDate").removeAttr("name");
            
            $("#setting_language_manage_minus").removeClass("button_icon_inline");
            $("#setting_language_manage_erase").click();
            
            ajax.send(
                true,
                $(this).prop("action"),
                $(this).prop("method"),
                $(this).serialize(),
                "json",
                false,
                null,
                function(xhr) {
                    ajax.reply(xhr, "#" + event.currentTarget.id);
                    
                    $("#form_setting_languageManage").prop("name", propNameLanguageManage);
                    $("#form_setting_languageManageDate").prop("name", propNameLanguageManageDate);
                },
                null,
                null
            );
        });
    };
    
    // Function private
    function languageManage() {
        var index = 1;
        var code = "";
        var eventAjax = "";
        
        $("#form_setting_language").on("change", "", function() {
            $("#setting_language_manage_erase").click();
            
            index = $(this).prop("selectedIndex");
            code = $("#form_setting_language").find("option").eq(index).val();
            
            if (index > 2)
                $("#setting_language_manage_minus").show();
            else
                $("#setting_language_manage_minus").hide();
        });
        
        $("#setting_language_manage_modify").on("click", "", function() {
            eventAjax = "modifyLanguage";
            
            $("#setting_language_manage_container").show();
            
            var valueCodeSelected = $("#form_setting_language").find(":selected").val();
            var valueDateSelected = $("#form_setting_language").find(":selected").text().replace(valueCodeSelected + " | ", "");
            
            $("#form_setting_languageManage").prop("disabled", true);
            $("#form_setting_languageManage").val(valueCodeSelected);
            $("#form_setting_languageManageDate").val(valueDateSelected);
        });
        
        $("#setting_language_manage_plus").on("click", "", function() {
            eventAjax = "createLanguage";
            
            $("#setting_language_manage_container").show();
            
            $("#form_setting_languageManage").prop("disabled", false);
            $("#form_setting_languageManage").val("");
            $("#form_setting_languageManageDate").val("");
        });
        
        $("#setting_language_manage_minus").on("click", "", function() {
            popupEasy.create(
                window.text.warning,
                "<p>" + window.textSetting.label_1 + "</p>",
                function() {
                    popupEasy.close();
                    
                    ajax.send(
                        true,
                        window.url.cpSettingLanguageManage,
                        "post",
                        {
                            'event': "deleteLanguage",
                            'code': code,
                            'token': window.session.token
                        },
                        "json",
                        false,
                        null,
                        function(xhr) {
                            ajax.reply(xhr, "");
                            
                            if (xhr.response.messages.success !== undefined) {
                                $("#setting_language_manage_minus").removeClass("button_icon_inline");
                                $("#setting_language_manage_erase").click();
                                
                                $("#form_setting_language").find("option").eq(index).remove();
                                $(".form_language_codeText").find("option").eq(index).remove();
                            }
                        },
                        null,
                        null
                    );
                },
                function() {
                    popupEasy.close();
                }
            );
        });
        
        $("#setting_language_manage_confirm").on("click", "", function() {
            var code = $("#form_setting_languageManage").val();
            var date = $("#form_setting_languageManageDate").val();
            
            ajax.send(
                true,
                window.url.cpSettingLanguageManage,
                "post",
                {
                    'event': eventAjax,
                    'code': code,
                    'date': date,
                    'token': window.session.token
                },
                "json",
                false,
                null,
                function(xhr) {
                    ajax.reply(xhr, "");
                    
                    if (xhr.response.messages.success !== undefined) {
                        $("#form_setting_language").append("<option value=\"" + code + "\">" + code + "</option>");
                        $(".form_language_codeText").append("<option value=\"" + code + "\">" + code + "</option>");
                        
                        $("#setting_language_manage_erase").click();
                    }
                },
                null,
                null
            );
        });
        
        $("#setting_language_manage_erase").on("click", "", function() {
            $("#form_setting_languageManage").val("");
            $("#form_setting_languageManageDate").val("");
            $("#setting_language_manage_container").hide();
        });
    }
}