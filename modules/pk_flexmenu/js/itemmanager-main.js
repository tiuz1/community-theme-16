$(document).ready(function () {
	String.prototype.replaceAll = function (token, newToken, ignoreCase) {
        var _token;
        var str = this + "";
        var i = -1;

        if (typeof token === "string") {

            if (ignoreCase) {

                _token = token.toLowerCase();

                while ((
                    i = str.toLowerCase().indexOf(
                        token, i >= 0 ? i + newToken.length : 0
                    )) !== -1) {
                    str = str.substring(0, i) +
                        newToken +
                        str.substring(i + token.length);
                }

            } else {
                return this.split(token).join(newToken);
            }

        }
        return str;
    };   
 
    $('#leftproduct_autocomplete_input')
        .autocomplete('ajax_products_list.php', {
            minChars: 3,
            autoFill: true,
            max: 20,
            matchContains: true,
            mustMatch: true,
            scroll: false,
            cacheLength: 0,
            extraParams: {
                excludeIds: -1
            },
            formatItem: function (item) {
                return item[1] + ' - ' + item[0];
            }
        }).result(function (event, data, formatted) {
            if (data == null)
                return false;
            var productId = data[1];
            var productName = data[0];
            var prodImg = getProducts(productId);
            var val = "PRD" + productId;
            $("#leftproductsitems").append("<li data-value=\"" + val + "\"><img src=\""+prodImg+"\" alt=\"\"/>" + productName + "<br/><span class='removeThis'></span></li>");
            $('#leftproduct_autocomplete_input').val('');        
            serializeItData("#leftproductsitems", "#leftproductsitemsInput", "left_products");
        });
    
    $('#product_autocomplete_input')
        .autocomplete('ajax_products_list.php', {
            minChars: 3,
            autoFill: true,
            max: 20,
            matchContains: true,
            mustMatch: true,
            scroll: false,
            cacheLength: 0,
            extraParams: {
                excludeIds: -1
            },
            formatItem: function (item) {
                return item[1] + ' - ' + item[0];
            }
        }).result(function (event, data, formatted) {
            if (data == null)
                return false;
            var productId = data[1];
            var productName = data[0];
            var prodImg = getProducts(productId);
            var val = "PRD" + productId;
            $("#right_product_id_curr").append("<li data-value=\"" + val + "\"><img src=\""+prodImg+"\" alt=\"\"/>" + productName + "<br/><span class='removeThis'></span></li>");
            $('#product_autocomplete_input').val('');        
            serializeItData("#right_product_id_curr", "#right_product_id", "right_products");
        });
    
    $('#mainproduct_autocomplete_input')
        .autocomplete('ajax_products_list.php', {
            minChars: 3,
            autoFill: true,
            max: 20,
            matchContains: true,
            mustMatch: true,
            scroll: false,
            cacheLength: 0,
            extraParams: {
                excludeIds: -1
            },
            formatItem: function (item) {
                return item[1] + ' - ' + item[0];
            }
        }).result(function (event, data, formatted) {
            if (data == null)
                return false;
            var productId = data[1];
            var productName = data[0];
            var prodImg = getProducts(productId);
            var val = "PRD" + productId;
            $("#mainproductsitems").append("<li data-value=\"" + val + "\"><img src=\""+prodImg+"\" alt=\"\"/>" + productName + "<br/><span class='removeThis'></span></li>");
            $('#mainproduct_autocomplete_input').val('');        
            serializeItData("#mainproductsitems", "#mainproductsitemsInput", "main_products");
        });
        
    $("#bottomavailableItems").change(function() {
    	addIt("#bottomavailableItems", "#bottomitems", "#bottomitemsInput", "bottom_links");    	
    });
    $("#mainlinksavailableItems").change(function() {
    	addIt("#mainlinksavailableItems", "#mainlinksitems", "#mainlinksitemsInput", "main_links");    	
    });
    $("#maincmsp").change(function() {
        sectionData($(this).data("dbname"), $(this).find('option:selected').val()); 
    });
    $("#mainvid").change(function() {
        sectionData($(this).data("dbname"), $(this).val()); 
    });
    $("#leftvid").change(function() {
        sectionData($(this).data("dbname"), $(this).val()); 
    });
    $("#rightvid").change(function() {
        sectionData($(this).data("dbname"), $(this).val()); 
    });
    $("#bottomvid").change(function() {
        sectionData($(this).data("dbname"), $(this).val()); 
    });
    $(".imagelink").change(function(){
       sectionData($(this).data("dbname"), $(this).val());       
    });
    $(".removeimg").click(function(){
       removeImage($(this).closest('.cpanel').data("section-name"));
       return false;
    });
    $(".removemainimg").click(function(){
       removeImage("main");
       return false;
    });
	$(".hide_left_panel").click(function(){
        if ($(".cleft_panel").hasClass("active")) {
            $(".width-long").addClass("hide");
            $(".width-short").removeClass("hide");
        } else {
            $(".width-short").addClass("hide");
            $(".width-long").removeClass("hide");
        }       
    });
    // update images in DB
	$("#right_image").change(function() {
		getUrl(this, "#right_image_prev", "right_image");
	});

	$("#left_image").change(function() {
		getUrl(this, "#left_image_prev", "left_image");
	});    

	$("#bottom_image").change(function() {
		getUrl(this, "#bottom_image_prev", "bottom_image");
	});

    $("#main_image").change(function() {
        getUrl(this, "#main_image_prev", "main_image");
    });
    // update menu item label
    $('#topLevelLinks').change(function(){
        updateMenuItem($(this).find('option:selected').val());
    });
    // state of menu item
    $("#active_item").change(function(){            
        updateMenuState($(this).val()); 
        if ($(this).val() == 1) {
            $(this).val(0) } 
        else {
            $(this).val(1) }
    });

    $("#narrow").change(function(){
        var th = $(this);
        updateMenuType(th.val());
        if (th.val() == 1) {
            th.val(0);
            $("#content_panel").addClass("narr");
            $(".background-section").hide('fast');
        } 
        else {
            th.val(1);            
            $("#content_panel").removeClass("narr");
            $(".background-section").show('fast');
        }
    });

	$(".removeThis").live("click", function() {	
		var thisRemove = $(this).parent();
		var item = $(this).parent().data("value");
		var menuID = $("#id_menu").val();
		var field = $(this).closest("ul").data("field");
		$.ajax({                
            data: 'menuID='+menuID+'&removeItem=1&field='+field+'&item='+item,
            success: function(result){
               $(thisRemove).hide(300, function() {
                    $(thisRemove).remove()
                });
               console.log(result);
            }
        });		

	});

	
    function getProducts(pID) {
		var tmp = 0;
		$.ajax({
		    async:false,
		    url: '../modules/pk_flexmenu/ajax/getimages.php',
		    data: 'pID='+pID,
		    success: function(result){
				tmp = result;
		    }		    
		});	
		return tmp;
	}

    // ##########################################   type switcher
  
    $(".selectors li label").click(function () {
        var type = $(this).data("type");
        var name = $(this).closest('.cpanel').data("section-name");
        $(this).closest(".panel-container").find(".margin_field").hide();
        $(this).closest(".panel-container").find("[data-type-content='"+type+"']").fadeIn('fast');
        sectionType(name, $(this).next('input').val());
        $(this).closest('.selectors').find('li').removeClass();
        $(this).closest('li').addClass('act');
    });

    // ####################################################    section switcher        
    // ##### switcher
    $(".section-switcher i").click(function () {    
        if ($(this).hasClass('checked')) {                        
            $(this).removeClass('checked');
            $(this).closest('.cpanel').removeClass('edit')
        } else {
            $(this).addClass('checked');
            $(this).closest('.cpanel').addClass('edit');
        }           
    });   
    // ##### close section button
    $(".section-switcher strong").click(function () {    
        if ($(this).parent().find('i').hasClass('checked')) {            
            $(this).parent().find('i').removeClass('checked');
            $(this).closest('.cpanel').removeClass('edit');         
        }         
    });
    // ##### indicator state
	$(".section-switcher label").click(function(){
		if ($(this).closest(".cpanel").hasClass('active')) {              
            $(this).closest(".cpanel").removeClass('active');         
        } else {
            $(this).closest(".cpanel").addClass('active');            
        } 
		sectionState($(this).data("section"), $(this).closest(".cpanel").hasClass('active') ? 1 : 0);		        
	});
    // ####################################################    db functions      

    function updateMainImage(th, field) {
        console.log(th);
        var menuID = $("#id_menu").val();
        //$.ajax({                
          //  data: 'updateMainImage=1&field='+field+'&state='+th.files[0]["name"]+'menuID='+menuID,
//            success: function(result){
  //              console.log(result);
    //        }
      //  });
    }  

    function sectionState(name, state) {
        var menuID = $("#id_menu").val();
        $.ajax({                
            data: 'menuID='+menuID+'&changeState=1&name='+name+'&state='+state,
            success: function(result){
                console.log(result);
            }
        });
    }
    function sectionType(name, type) {
        //console.log(name+"="+type);
        var menuID = $("#id_menu").val();
        $.ajax({                
            data: 'menuID='+menuID+'&changeType=1&name='+name+'&type='+type,
            success: function(result){
               //console.log(result);
            }
        });
    }
    function sectionData(name, data) {
        //console.log(name+"="+type);
        var menuID = $("#id_menu").val();
        $.ajax({                
            data: 'menuID='+menuID+'&changeData=1&name='+name+'&data='+data,
            success: function(result){
              console.log(result);
            }
        });
    }
    function removeData(field) {
        //console.log(name+"="+type);
        var menuID = $("#id_menu").val();
        $.ajax({                
            data: 'menuID='+menuID+'&removeData=1&field='+field,
            success: function(result){
              // console.log(result);
            }
        });
    }
    function removeImage(type) {        
        var menuID = $("#id_menu").val();
        $.ajax({                
            data: 'menuID='+menuID+'&removeImage=1&type='+type,
            success: function(result){
               console.log(result);
               $("."+type+"imagepreview").hide();
               $("#"+type+"imagelink").val('');               
            }
        });
    }
    function updateMenuItem(val, link, langid) {
        //console.log(name+"="+type);
        var menuID = $("#id_menu").val();
        var shopID = $(".menuItem").data("shop-id");
        var langID = $(".menuItem").data("lang-id");
        $.ajax({                
            data: 'menuID='+menuID+'&updateMenuItem=1&val='+val+'&shopid='+shopID+'&langid='+langID,
            success: function(result){
              $(".after-label-add-content").slideDown();
              if(menuID == "NONE") $("#id_menu").val(result);
              console.log(result);
            }
        });
    }
    /*function updateMenuItemLink(val, langid) {
        //console.log(name+"="+type);
        var v = val.replace(/&/g,"%26");
        var menuID = $("#id_menu").val();
        var shopID = $(".menuItem").data("shop-id");
        $.ajax({                
            data: 'menuID='+menuID+'&updateMenuItemLink=1&val='+v+'&langid='+langid+'&shopid='+shopID,
            success: function(result){          
            }
        });
    }*/
    function updateMenuState(val) {
        //console.log(name+"="+type);
        var menuID = $("#id_menu").val();
        var shopID = $(".menuItem").data("shop-id");
        $.ajax({                
            data: 'menuID='+menuID+'&updateMenuState=1&val='+val+'&shopid='+shopID,
            success: function(result){  
            }
        });
    }
    function updateMenuType(val) {
        //console.log(name+"="+type);
        var menuID = $("#id_menu").val();
        var shopID = $(".menuItem").data("shop-id");
        $.ajax({                
            data: 'menuID='+menuID+'&updateMenuType=1&val='+val+'&shopid='+shopID,
            success: function(result){  

            }
        });
    }    

	function addIt(avail, item, input, fieldName)
	{
		$(avail+" option:selected").each(function(i){
			var val = $(this).val();
			var text = $(this).text();
			text = text.replace(/(^\s*)|(\s*$)/gi,"");
			if (val == "PRODUCT")
			{
				val = prompt("Set ID product");
				if (val == null || val == "" || isNaN(val))
					return;
				text = "Product ID"+val;
				val = "PRD"+val;
			}
			$(item).append("<li data-value=\""+val+"\">"+text+" <span class='removeThis'></span></li>");
		});
		serializeItData(item, input, fieldName);
		return false;
	}
    function addCMSP(item, data)
    {
        serializeItData(item, input, fieldName);        
        return false;
    }
    

	function removeIt(item, input)
	{
		$(item+" option:selected").each(function(i){
			$(this).remove();
		});
		serializeIt(item, input);
		return false;
	}
	function serializeIt(item, input)
	{
		var options = "";
		$(item+" option").each(function(i){
			options += $(this).val()+",";
		});
		$(input).val(options.substr(0, options.length - 1));
	}
	function serializeItData(item, input, fieldName)
	{		
		var options = "";
		$(item+" li").each(function(i){
			options += $(this).data("value")+",";
		});
		$(input).val(options.substr(0, options.length - 1));
		sectionData(fieldName, options.substr(0, options.length - 1));
	}

	function getUrl(input, elID, field) {
        if (input.files && input.files[0]) {
        	var menuID = $("#id_menu").val();
            var reader = new FileReader();
            var d = new Date();
			var m = d.getMinutes();
			var s = d.getSeconds();

            reader.onload = function (e) {
                $(elID).attr("src", e.target.result).show();
            }
            reader.readAsDataURL(input.files[0]);
            $.ajax({                
	            data: 'menuID='+menuID+'&changeData=1&name='+field+'&data='+input.files[0]["name"],
	            success: function(result){
	              console.log(result);
	            }
	        });
        }
	}


	var input_right = document.getElementById("right_image"),
		input_left = document.getElementById("left_image"), 
        input_main = document.getElementById("main_image"), 
		input_bottom = document.getElementById("bottom_image"),
    	formdata = false;

    if (window.FormData) {
        formdata = new FormData();
        document.getElementById("uploadRightImage").style.display = "none";
        document.getElementById("uploadMainImage").style.display = "none";
        document.getElementById("uploadLeftImage").style.display = "none";
        document.getElementById("uploadBottomImage").style.display = "none";
    }
    
    input_right.addEventListener("change", function (evt) {
        uploader(this, "right_response");
    }, false);
    input_left.addEventListener("change", function (evt) {
        uploader(this, "left_response");
    }, false);
    input_main.addEventListener("change", function (evt) {
        uploader(this, "main_response");
    }, false);
    input_bottom.addEventListener("change", function (evt) {
        uploader(this, "bottom_response");
    }, false);

	function uploader(thisa, resp) {

		
		document.getElementById(resp).innerHTML = "Uploading . . ."
        var i = 0, len = thisa.files.length, img, reader, file;
    
        for ( ; i < len; i++ ) {
            file = thisa.files[i];
    
            if (!!file.type.match(/image.*/)) {
                if ( window.FileReader ) {
                    reader = new FileReader();
                    reader.onloadend = function (e) { 
                        //showUploadedItem(e.target.result, file.fileName);
                    };
                    reader.readAsDataURL(file);
                }
                if (formdata) {
                    formdata.append("images[]", file);
                    formdata.append("token", token);
                }
            }   
        }

        var d = new Date();
		var m = d.getMinutes();
		var s = d.getSeconds();

        if (formdata) {
            $.ajax({
                url: "../modules/pk_flexmenu/ajax/upload.php",
                type: "POST",
                data: formdata,
                processData: false,
                contentType: false,
                success: function (res) {
                    document.getElementById(resp).innerHTML = res; 
                }
            });
        }

	}
});