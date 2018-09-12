  function dropSroll(url,pageSize){
    var page=0;
    // var user_paper=['限制借条','正常服务'];
   
    var dropload=$('.content_div').dropload({
        
        domUp : {
            domClass   : 'dropload-up',
            domRefresh : '<div class="dropload-refresh ">↓下拉刷新</div>',
            domUpdate  : '<div class="dropload-update ">↑释放更新</div>',
            domLoad    : '<div class="dropload-load "><span class="loading"></span>加载中...</div>'
        },
        domDown : {
            domClass   : 'dropload-down',
            domRefresh : '<div class="dropload-refresh ">↑上拉加载更多</div>',
            domLoad    : '<div class="dropload-load "><span class="loading"></span>加载中...</div>',
            domNoData  : '<div class="dropload-noData ">暂无数据</div>'
        },
        
        loadUpFn : function(me){
            page=1;
            // $.ajax({
            //     type: 'POST',
            //     url: "{:url('user/index/ajax_overdue')}",
            //     dataType: 'json',
            //     data:{'page':page},
            //     success: function(data){
            //         console.log(data)
            //     	var lists=data.data;
            //         var result = '';
                
            //         for(var i in lists){
            //             result += " <li class='confirm_items'>"+
            //                         "<ol class='cofirm_con_third clearfix'>"+
            //                             "<li>"+
            //                                     lists[i].borrower_name +
            //                             "</li>"+
            //                             "<li>"+
            //                                     lists[i].money +
            //                             "</li>"+
            //                             "<li>"+
            //                                     lists[i].overdue_day +
            //                             "</li>"+
            //                         "</ol>"+
            //                     "</li>"
            //         }
            //         // 为了测试，延迟1秒加载
            //         setTimeout(function(){
            //             $('.confirm_list').html(result);
            //             me.setHasData(true)
            //             // 每次数据加载完，必须重置
            //             me.resetload();
                    
            //         },1000);
            //     },
            //     error: function(xhr, type){
            //     	 console.log('Ajax error!');
            //         // 即使加载出错，也得重置
            //         //dropload.resetload();
            //     }
            // })

            // 前端没有解决这个问题，换成刷新页面
            window.location.reload();
            me.resetload();
        
        },
        loadDownFn : function(me){
            
            page++;
            
            var result = '';
            $.ajax({
                type: 'POST',
                url: url,
                dataType: 'json',
                data:{'page':page},
                success: function(data){
                    var lists=data.data;
                    
                    var objLength=Object.keys(lists);
                        var z=1;
                        for(var i in lists){

                            z++;
                            
                            // console.log()
                            result += " <li class='confirm_items'>"+
                                    "<ol class='cofirm_con_third clearfix'>"+
                                        "<li>"+
                                                lists[i].uname +
                                        "</li>"+
                                        "<li>"+
                                                lists[i].idcard +
                                        "</li>"+
                                        "<li>"+
                                                lists[i].overdue_day +
                                        "</li>"+
                                        "<li>已公示</li>" +
                                    "</ol>"+
                                "</li>";
                            
                        } 
                        // 判断每次加载的length是否小于一页显示的条数
                        if(objLength.length < pageSize){
                             me.lock();
                             // 显示无数据
                             me.noData();
                        }
          
                    // 为了测试，延迟1秒加载
                    setTimeout(function(){
                        $('.confirm_list').append(result);
                        // 每次数据加载完，必须重置
                        me.resetload();
                    },500); 
                },
                error: function(xhr, type){
                    console.log('Ajax error!');
                    window.location.reload();
                    // 即使加载出错，也得重置
                    //dropload.resetload();
                }
            });
        
        }
    });
    
  } 
