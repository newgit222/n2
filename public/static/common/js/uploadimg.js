// url 上传地址

function uploadimgt(url,upid,veiwid){

        var uploader__upload_5 = WebUploader.create({
            withCredentials: true,                                                             // 跨域请求提供凭证
            auto: true,                                                                        // 选完文件后，是否自动上传
            duplicate: true,                                                                   // 同一文件是否可以重复上传
            swf: '/Public/libs/lyui/dist/swf/uploader.swf',                          // swf文件路径
            server: url, // 文件接收服务端
            pick: '#_upload_5',                                       // 选择文件的按钮
            resize: false,                                                                     // 不压缩image, 默认如果是jpeg，文件上传前会压缩一把再上传！
            //fileNumLimit: 1,                                                                 // 验证文件总数量, 超出则不允许加入队列
            fileSingleSizeLimit:2*1024*1024, // 验证单个文件大小是否超出限制, 超出则不允许加入队列
            // 文件过滤
            accept: {
                title: 'Images',
                extensions: "gif,jpg,jpeg,bmp,png",
                mimeTypes: 'image/gif,image/jpg,image/jpeg,image/bmp,image/png'
            }
        });

        // 文件上传过程中创建进度条实时显示。
        uploader__upload_5.on( 'uploadProgress', function(file, percentage ) {
            $('#_upload_preview_5').removeClass('hidden');
            var $li = $( '#_upload_preview_5'),
                $percent = $li.find('.progress .progress-bar');
            // 避免重复创建
            if ( !$percent.length ) {
                $percent = $('<div class="progress"><div class="progress-bar"></div></div>')
                        .appendTo( $li )
                        .find('.progress-bar');
            }
            $percent.css('width', percentage * 100 + '%');
        });

        // 完成上传完了，成功或者失败，先删除进度条。
        uploader__upload_5.on('uploadComplete', function(file) {
            $( '#_upload_preview_5' ).find('.progress').remove();
        });

        // 文件上传成功，给item添加成功class, 用样式标记上传成功。
        uploader__upload_5.on('uploadSuccess', function(file , response) {
            $('#_upload_preview_5').addClass('upload-state-done');
            if (eval('response').status == 0) {
                $.alertMessager(response.message);
            } else {
                $( '#_upload_input_5').attr('value', response.id);
                $( '#_upload_preview_5 img').attr('src', response.url);
            }
        });

        // 上传错误
        uploader__upload_5.on("error",function (type){
            if (type=="Q_TYPE_DENIED") {
                $.alertMessager('该文件格式不支持');
            } else if(type=="F_EXCEED_SIZE") {
                $.alertMessager("文件大小不允许超过2MB");
            } else if(type=="Q_EXCEED_NUM_LIMIT") {
                $.alertMessager("超过允许的文件数量");
            } else {
                $.alertMessager(type);
            }
        });

        // 文件上传失败，显示上传出错。
        uploader__upload_5.on('uploadError', function(file) {
            $.alertMessager('error');
            var $li = $('#_upload_preview_5'),
                $error = $li.find('div.error');
            // 避免重复创建
            if (!$error.length) {
                $error = $('<div class="error"></div>').appendTo($li);
            }
            $error.text('上传失败');
        });

        // 删除图片
        $(document).on('click', '#_upload_list_5 .remove-picture', function() {
            $('#_upload_input_5' ).val('') //删除后覆盖原input的值为空
            $('#_upload_preview_5').addClass('hidden');
        });
    }