/* global utility, loader, flashBag */

var upload = new Upload();

function Upload() {
    // Vars
    var self = this;
    
    var tagContainer;
    var tagProgressBar;
    var tagImageRefresh;
    
    var chunkSize;
    
    var file;
    var tmp;
    var uploadStarted;
    var uploadPaused;
    var uploadAborted;
    var chunkCurrent;
    var chunkPause;
    var timeStart;
    var totalTime;
    var timeLeft;
    
    // Properties
    self.setTagContainer = function(value) {
        tagContainer = value;
    };
    
    self.setTagProgressBar = function(value) {
        tagProgressBar = value;
    };
    
    self.setTagImageRefresh = function(value) {
        tagImageRefresh = value;
    };
    
    // Functions public
    self.init = function() {
        chunkSize = 0;

        file = null;
        tmp = 0;
        uploadStarted = false;
        uploadPaused = false;
        uploadAborted = false;
        chunkCurrent = 0;
        chunkPause = 0;
        timeStart = 0;
        totalTime = 0;
        timeLeft = 0;
        
        tagContainer = "";
        tagImageRefresh = "";
        tagProgressBar = "";
    };
    
    self.processFile = function() {
        $(tagContainer).find(".upload .file").on("change", "", function() {
            file = $(this)[0].files[0];
            
            var formData = new FormData();
            formData.append('file', file);
            
            $.ajax({
                'url': window.url.cpProfileUpload + "?action=change",
                'method': "post",
                'data': formData,
                'dataType': "json",
                'cache': false,
                'processData': false,
                'contentType': false,
                beforeSend: function() {
                },
                success: function(xhr) {
                    if (xhr.userActivity !== undefined && xhr.userActivity !== "") {
                        window.session.userActivity = xhr.userActivity;

                        self.reply(xhr, "");

                        return;
                    }

                    if (xhr.response.upload !== undefined) {
                        chunkSize = xhr.response.upload.processFile;
                        
                        if (xhr.response.upload.processFile.status === 1) {
                            resetValue("hide");
                            
                            flashBag.show(xhr.response.upload.processFile.text);

                            return;
                        }
                        
                        if (file !== null) {
                            resetValue("show");
                            
                            $(tagContainer).find(".upload .button_start").off("click").on("click", "", function() {
                                if (uploadStarted === false && uploadPaused === false)
                                    start();
                                else if (uploadStarted === true && uploadPaused === false)
                                    pause();
                                else if (uploadStarted === true && uploadPaused === true)
                                    resume();
                            });

                            $(tagContainer).find(".upload .button_stop").off("click").on("click", "", function() {
                                abort();
                            });
                        } 
                        else
                            resetValue("hide");
                    }

                    loader.hide();
                },
                error: function(xhr, status) {
                    loader.hide();
                },
                complete: function() {
                }
            });
        });
    };
    
    // Functions private
    function start() {
        if (file !== null) {
            uploadStarted = true;
            
            flashBag.show(window.textUpload.label_2);
            
            $(tagContainer).find(".upload .button_start span").text(window.textUpload.label_5);
            
            chunkCurrent = Math.ceil(file.size / chunkSize);

            sendChunk(0);
        }
    }
    
    function pause() {
        uploadPaused = true;
        
        $(tagContainer).find(".upload .button_start i").text("pause");
        $(tagContainer).find(".upload .button_start span").text(window.textUpload.label_6);
    }
    
    function resume() {
        uploadPaused = false;
        
        $(tagContainer).find(".upload .button_start i").text("play_arrow");
        $(tagContainer).find(".upload .button_start span").text(window.textUpload.label_5);
        
        sendChunk(chunkPause);
    }
    
    function abort() {
        uploadAborted = true;
        
        var xhr = new XMLHttpRequest();
        xhr.open("post", window.url.cpProfileUpload + "?action=abort&tmp=" + tmp, true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                var jsonParse = JSON.parse(xhr.response);
                
                if(xhr.status !== 200)
                    return;
                
                resetValue("hide");
                
                if (jsonParse.response.upload.processFile !== null)
                    flashBag.show(jsonParse.response.upload.processFile.text);
            }
        };
        
        xhr.send("");
    }
    
    function progress(start) {
        materialDesign.linearProgress(tagProgressBar, start, chunkCurrent);
        
        if (start % 5 === 0) {
            totalTime += (new Date().getTime() - timeStart);
            
            timeLeft = Math.ceil((totalTime / start) * (chunkCurrent - start) / 100);
            
            flashBag.show(timeLeft + window.textUpload.label_3);
        }
    }
    
    function sendChunk(chunk) {
        timeStart = new Date().getTime();
        
        if (uploadAborted === true)
            return;
        
        if (uploadPaused === true) {
            chunkPause = chunk;
            
            flashBag.show(window.textUpload.label_1);
            
            return;
        }
        
        var start = chunk * chunkSize;
        var stop = start + chunkSize;
        
        var reader = new FileReader();
        
        var blob = file.slice(start, stop);
        
        if (navigator.userAgent.indexOf("MSIE") !== -1)
            reader.readAsArrayBuffer(blob);
        else
            reader.readAsBinaryString(blob);

        reader.onloadend = function() {
            var xhr = new XMLHttpRequest();
            xhr.open("post", window.url.cpProfileUpload + "?action=start&tmp=" + tmp, true);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    var jsonParse = JSON.parse(xhr.response);
                    
                    if (xhr.status !== 200)
                        return;
                    
                    if (jsonParse.response.upload.processFile.status === 0) {
                        if (chunk === 0 || tmp === 0)
                            tmp = jsonParse.response.upload.processFile.tmp;

                        if (chunk < chunkCurrent) {
                            progress(chunk + 1);
                            
                            sendChunk(chunk + 1);
                        }
                        else
                            sendComplete();
                    }
                    else if (jsonParse.response.upload.processFile.status === 1) {
                        resetValue("hide");
                        
                        flashBag.show(jsonParse.response.upload.processFile.text);
                        
                        return;
                    }
                    else if (jsonParse.response.upload.processFile.status === 2)
                        sendComplete();
                }
            };
            
            xhr.send(blob);
        };
    }
    
    function sendComplete() {
        var xhr = new XMLHttpRequest();
        xhr.open("post", window.url.cpProfileUpload + "?action=finish&tmp=" + tmp + "&name=" + file.name, true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                var jsonParse = JSON.parse(xhr.response);

                if (xhr.status !== 200)
                    return;
                
                resetValue("hide");
                
                if (jsonParse.response.upload.processFile !== null)
                    flashBag.show(jsonParse.response.upload.processFile.text);
                
                if (tagImageRefresh !== "")
                    utility.imageRefresh(tagImageRefresh, 1);
            }
        };
        
        xhr.send("");
    }
    
    function resetValue(type) {
        materialDesign.linearProgress(tagProgressBar);
        
        $(tagContainer).find(".upload .button_start i").text("play_arrow");
        $(tagContainer).find(".upload .button_start span").text(window.textUpload.label_4);
        
        if (type === "show") {
            $(tagContainer).find(".upload .mdc-linear-progress").show();
            $(tagContainer).find(".upload .controls").show();
        }
        else if (type === "hide") {
            $(tagContainer).find(".upload .file").val("");
            $(tagContainer).find(".upload .mdc-linear-progress").hide();
            $(tagContainer).find(".upload .controls").hide();
            
            file = null;
        }
        
        tmp = 0;
        uploadStarted = false;
        uploadPaused = false;
        uploadAborted = false;
        chunkCurrent = 0;
        chunkPause = 0;
        timeStart = 0;
        totalTime = 0;
        timeLeft = 0;
    }
}