// Version 1.0.0

/* global utility, ajax */

function TableAndPagination() {
    // Vars
    var self = this;
    
    var urlRequest;
    var idResult;
    var selectOnlyOne;
    
    var current;
    var total;
    
    var clickedEvent;
    var sortOrderBy;
    
    var buttonsStatus;
    
    // Properties
    self.setButtonsStatus = function(value) {
        buttonsStatus = value;
    };
    
    // Functions public
    self.init = function() {
        urlRequest = "";
        idResult = "";
        selectOnlyOne = "";

        current = 0;
        total = 0;

        clickedEvent = false;
        sortOrderBy = false;

        buttonsStatus = "";
    };
    
    self.create = function(url, id, singleSelection) {
        urlRequest = url;
        idResult = id;
        selectOnlyOne = singleSelection;
        
        status();
        
        utility.linkPreventDefault();
        
        if (selectOnlyOne === true)
            utility.selectOnlyOneElement(idResult + " .table_tbody");
        
        resizeColumn();
    };
    
    self.search = function() {
        var parentField = idResult + " .tableAndPagination .mdc-text-field__input";
        var parentButton = idResult + " .tableAndPagination .mdc-text-field .material-icons";
        
        $(parentField).on("keyup", "", function(event) {
            if (clickedEvent === true)
                return;
            
            if (event.which === 13) {
                current = 0;
                
                send();
                
                clickedEvent = true;
            }
        });
        
        $(parentButton).on("click", "", function() {
            if (clickedEvent === true)
                return;
            
            current = 0;
            
            send();
            
            clickedEvent = true;
        });
    };
    
    self.pagination = function() {
        var parentPrevious = idResult + " .tableAndPagination .previous";
        var parentNext = idResult + " .tableAndPagination .next";
        
        $(parentPrevious).on("click", "", function() {
            if (clickedEvent === true)
                return;
            
            if (total > 1 && current > 0) {
                current --;
                
                send();
                
                clickedEvent = true;
            }
        });
        
        $(parentNext).on("click", "", function() {
            if (clickedEvent === true)
                return;
            
            if (total > 1 && current < (total - 1)) {
                current ++;
                
                send();
                
                clickedEvent = true;
            }
        });
    };
    
    self.sort = function(delegate) {
        var parent = delegate === true ? document : idResult + " table thead tr";
        var child = delegate === true ? idResult + " table thead tr" : "";
        
        $(parent).on("click", child, function(event) {
            var bodyRows = $(idResult).find("table tbody tr");
            
            var currentIndex = $(event.target).is("i") === false ? $(event.target).index() : $(event.target).parent().index();
            
            $(this).find("th i").hide();
            
            if (sortOrderBy === false) {
                $(this).find("th").eq(currentIndex).find("i").eq(0).show();
                $(this).find("th").eq(currentIndex).find("i").eq(1).hide();
                
                sortOrderBy = true;
            }
            else {
                $(this).find("th").eq(currentIndex).find("i").eq(0).hide();
                $(this).find("th").eq(currentIndex).find("i").eq(1).show();
                
                sortOrderBy = false;
            }
            
            bodyRows.sort(function(a, b) {
                var result = 0;
                var first = $.trim($(a).children("td").eq(currentIndex).text().toLowerCase());
                var second = $.trim($(b).children("td").eq(currentIndex).text().toLowerCase());
                
                if (first !== "" && second !== "") {
                    if (sortOrderBy === true) {
                        if ($.isNumeric(first) === false)
                            result = first.localeCompare(second);
                        else
                            result = first - second;
                    }
                    else {
                        if ($.isNumeric(first) === false)
                            result = second.localeCompare(first);
                        else
                            result = second - first;
                    }
                }
                
                return result;
            });
            
            if (bodyRows.length > 1) {
                $.each(bodyRows, function(key, value) {
                    $(idResult).find("table tbody").append(value);
                });
            }
        });
    };
    
    self.populate = function(xhr) {
        $(idResult).find(".tableAndPagination .mdc-text-field__input").val("");
        $(idResult).find("table tbody").css("visibility", "visible");
        
        if (xhr.response.values !== undefined) {
            $(idResult).find(".tableAndPagination .mdc-text-field__input").val(xhr.response.values.search.value);
            $(idResult).find(".tableAndPagination .text").html(xhr.response.values.pagination.text);
            
            if (xhr.response.values.count !== undefined) {
                $(idResult).find(".tableAndPagination .count").show();
                $(idResult).find(".tableAndPagination .count span").html(xhr.response.values.count);
            }
            
            if ($(idResult).find("table tbody").length > 0)
                $(idResult).find("table tbody").html(xhr.response.values.listHtml);
            else
                $(idResult).find(".container_list").html(xhr.response.values.listHtml);

            status();
            
            resizeColumn();
        }
    };
    
    // Functions private
    function status() {
        var textHtml = $(idResult).find(".tableAndPagination .text").text();
        
        if (textHtml !== undefined) {
            var textSplit = textHtml.split("/");
            var valueA = parseInt($.trim(textSplit[0]));
            var valueB = parseInt($.trim(textSplit[1]));
            
            if (valueA > valueB && $(idResult).find("table tbody tr").length === 0)
                $(idResult).find(".tableAndPagination .previous").click();
            
            //current = valueA - 1;
            
            if (current < 0)
                current = 0;
            
            total = valueB;
        }
        
        $(idResult).find(".tableAndPagination .previous").addClass("disabled");
        $(idResult).find(".tableAndPagination .next").addClass("disabled");
        
        if (total > 1 && current > 0)
            $(idResult).find(".tableAndPagination .previous").removeClass("disabled");
        
        if (total > 1 && current < (total - 1))
            $(idResult).find(".tableAndPagination .next").removeClass("disabled");
        
        $.each($(idResult).find("table thead tr"), function(key, value) {
            $(value).find("th i").hide();
        });
        
        if (buttonsStatus === "show")
            $(idResult).find(".tableAndPagination .container_buttons").show();
    }
    
    function send() {
        var data = {
            'searchWritten': $(idResult).find(".tableAndPagination .mdc-text-field__input").val(),
            'paginationCurrent': current,
            'token': window.session.token
        };
        
        ajax.send(
            false,
            urlRequest,
            "post",
            data,
            "json",
            false,
            function() {
                $(idResult).find("table tbody").css("visibility", "hidden");
            },
            function(xhr) {
                ajax.reply(xhr, "");
                
                if (xhr.response.values !== undefined)
                    self.populate(xhr);
                
                utility.linkPreventDefault();
                
                if (selectOnlyOne === true)
                    utility.selectOnlyOneElement(idResult + " .table_tbody");
                
                resizeColumn();
                
                clickedEvent = false;
            },
            null,
            null
        );
    }
    
    function resizeColumn() {
        $(function() {
            $(idResult).find("table tbody tr td").resizable({
                handles: "e"
            });
        });
    }
}