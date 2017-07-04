/* global utility, ajax, popupEasy */

var controlPanelUser = new ControlPanelUser();

function ControlPanelUser() {
    // Vars
    var self = this;
    
    var widthType = "";
    var widthTypeOld = "";
    
    // Properties
    
    // Functions public
    $(window).resize(function() {
        resetView();
    });
    
    self.init = function() {
        resetView();
        
        selection();
        
        utility.wordTag("#form_user_roleId");
        
        $("#form_cp_user_creation").on("submit", "", function(event) {
            event.preventDefault();
            
            ajax.send(
                true,
                true,
                $(this).prop("action"),
                $(this).prop("method"),
                $(this).serialize(),
                "json",
                false,
                null,
                function(xhr) {
                    ajax.reply(xhr, "#" + event.currentTarget.id);
                },
                null,
                null
            );
        });
    };
    
    // Function private
    function selection() {
        var table = new Table();
        table.setButtonsStatus("show");
        table.init(window.url.cpUsersSelection, "#cp_users_selection_desktop_result", true);
        table.search(true);
        table.pagination(true);
        table.sort(true);
        
        $(document).on("click", "#cp_users_selection_desktop_result .refresh", function() {
            ajax.send(
                true,
                false,
                window.url.cpUsersSelection,
                "post",
                {
                    'event': "refresh",
                    'token': window.session.token
                },
                "json",
                false,
                null,
                function(xhr) {
                    ajax.reply(xhr, "");
                    
                    table.populate(xhr);
                },
                null,
                null
            );
        });
        
        $(document).on("click", "#cp_users_selection_desktop_result .delete_all", function() {
            popupEasy.create(
                window.text.warning,
                window.textUser.deleteAllUsers,
                function() {
                    popupEasy.close();
                    
                    ajax.send(
                        true,
                        false,
                        window.url.cpUserDeletion,
                        "post",
                        {
                            'event': "deleteAll",
                            'token': window.session.token
                        },
                        "json",
                        false,
                        null,
                        function(xhr) {
                            ajax.reply(xhr, "");

                            table.populate(xhr);
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
        
        $(document).on("click", "#cp_users_selection_desktop_result .cp_user_deletion", function() {
            var id = $.trim($(this).parents("tr").find(".id_column").text());
            
            deletion(id);
        });
        
        $("#cp_users_selection_send").on("click", "", function(event) {
            var id = $.trim($("#cp_users_selection_desktop_result").find(".checkbox_column input:checked").parents("tr").find(".id_column").text());

            ajax.send(
                true,
                true,
                window.url.cpUsersSelection,
                "post",
                {
                    'id': id,
                    'token': window.session.token
                },
                "json",
                false,
                function() {
                    $("#cp_user_selection_result").html("");
                },
                function(xhr) {
                    selectionResult(xhr, "#" + event.currentTarget.id);
                },
                null,
                null
            );
        });
        
        $("#form_cp_users_selection").on("submit", "", function(event) {
            event.preventDefault();

            ajax.send(
                true,
                true,
                $(this).prop("action"),
                $(this).prop("method"),
                $(this).serialize(),
                "json",
                false,
                function() {
                    $("#cp_user_selection_result").html("");
                },
                function(xhr) {
                    selectionResult(xhr, "#" + event.currentTarget.id);
                },
                null,
                null
            );
        });
    }
    
    function selectionResult(xhr, tag) {
        ajax.reply(xhr, tag);
        
        if ($.isEmptyObject(xhr.response) === false && xhr.response.render !== undefined) {
            $("#cp_user_selection_result").html(xhr.response.render);

            profile();
            
            $("#cp_user_deletion").on("click", "", function() {
               deletion(xhr.urlExtra);
            });
        }
    }
    
    function profile() {
        utility.wordTag("#form_user_roleId");
        
        $("#form_cp_user_profile").on("submit", "", function(event) {
            event.preventDefault();
            
            ajax.send(
                true,
                true,
                $(this).prop("action"),
                $(this).prop("method"),
                $(this).serialize(),
                "json",
                false,
                null,
                function(xhr) {
                    ajax.reply(xhr, "#" + event.currentTarget.id);
                },
                null,
                null
            );
        });
    }
    
    function deletion(id) {
        popupEasy.create(
            window.text.warning,
            window.textUser.deleteUser,
            function() {
                popupEasy.close();

                ajax.send(
                    true,
                    false,
                    window.url.cpUserDeletion,
                    "post",
                    {
                        'event': "delete",
                        'id': id,
                        'token': window.session.token
                    },
                    "json",
                    false,
                    function() {
                        $("#cp_user_selection_result").html("");
                    },
                    function(xhr) {
                        ajax.reply(xhr, "");
                    },
                    null,
                    null
                );
            },
            function() {
                popupEasy.close();
            }
        );
    }
    
    function resetView() {
        widthType = utility.widthCheck(992);
        
        if ((widthType === "desktop" || widthType === "mobile") && widthTypeOld !== widthType) {
            $("#cp_users_selection_desktop_result").find(".checkbox_column input[type='checkbox']").prop("checked", false);
            $("#cp_users_selection_mobile").find("select").val("");
            $("#cp_user_selection_result").html("");
            
            widthTypeOld = widthType;
        }
    }
}