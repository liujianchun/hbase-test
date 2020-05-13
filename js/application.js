/* --- YAF_CG_AUTO_UPGRADE -- */
/* You should not change these lines below until YAF_CG_AUTO_UPGRADE_END */

$(function() {
  // for api document page
  $("#interface-document-container a").click(function() {
    $("h4").removeClass("current");
    $($(this).attr("href")).addClass("current");
    return true;
  });
  if(window.location.hash != "") {
    $(window.location.hash).addClass("current");
  }

  // for form components
  $(document).on('click', 'a.checkbox-list-check-all', function() {
    if($(this).text() == '全选') {
      $('input[type=checkbox]', $(this).parent()).prop('checked', 'checked');
      $(this).text('取消选择');
    } else {
      $('input[type=checkbox]', $(this).parent()).prop('checked', null);
      $(this).text('全选');
    }
  });

  $('#performance_trace_block .summary').click(function() {
    $('#performance_trace_detail').toggle();
  });
  $('[data-toggle="tooltip"]').tooltip();
});

jQuery(function($) {
  $.extend({
    form: function(url, data, method) {
      if(method == null) method = 'POST';
      if(data == null) data = {};

      var form = $('<form>').attr({
        method: method,
        action: url
      }).css({
        display: 'none'
      });

      var addData = function(name, data) {
        if($.isArray(data)) {
          for(var i = 0; i < data.length; i++) {
            var value = data[i];
            addData(name + '[]', value);
          }
        } else if(typeof data === 'object') {
          for(var key in data) {
            if(data.hasOwnProperty(key)) {
              addData(name + '[' + key + ']', data[key]);
            }
          }
        } else if(data != null) {
          form.append($('<input>').attr({
            type: 'hidden',
            name: String(name),
            value: String(data)
          }));
        }
      };

      for(var key in data) {
        if(data.hasOwnProperty(key)) {
          addData(key, data[key]);
        }
      }

      return form.appendTo('body');
    }
  });
});

function buildPostFormAndSubmit(action, data) {
  $.form(action, data).submit();
}

function downloadHighchartsChartExcel(chart, name) {
  var html = '<?xml version="1.0"?>';
  html += '<ss:Workbook xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
  html += '<ss:Styles>';
  html += '<ss:Style ss:ID="align_center"><ss:Alignment ss:Horizontal="Center" ss:Vertical="Center" /></ss:Style>';
  html += '</ss:Styles>';
  html += '<ss:Worksheet ss:Name="' + name + '">';
  html += '<ss:Table>';

  for(var i = 0; i <= chart.yAxis[0].series.length; i++) {
    html += '<ss:Column ss:Width="150"/>';
  }

  html += '<ss:Row ss:Height="25">';
  html += '<ss:Cell ss:StyleID="align_center"><ss:Data ss:Type="String">时间</ss:Data></ss:Cell>';
  $(chart.yAxis[0].series).each(function(i, yAxisItem) {
    var title = yAxisItem.legendItem.textStr;
    html += '<ss:Cell ss:StyleID="align_center"><ss:Data ss:Type="String">' + title + '</ss:Data></ss:Cell>';
  });

  html += '</ss:Row>';
  $(chart.xAxis[0].categories).each(function(i, category) {
    html += '<ss:Row ss:Height="22">';
    html += '<ss:Cell ss:StyleID="align_center"><ss:Data ss:Type="String">' + category + '</ss:Data></ss:Cell>';
    $(chart.yAxis[0].series).each(function(j, yAxisItem) {
      var value = yAxisItem.processedYData[i];
      if(!value) value = '-';
      html += '<ss:Cell ss:StyleID="align_center"><ss:Data ss:Type="String">' + value + '</ss:Data></ss:Cell>';
    });
    html += '</ss:Row>';
  });
  html += '</ss:Table>';
  html += '</ss:Worksheet>';
  html += '</ss:Workbook>';
  var url = window.URL.createObjectURL(new Blob([html], {type: "octet/stream"}));
  var a = document.createElement('a');
  a.setAttribute('href', url);
  a.setAttribute('download', name + ".xls");
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
}

/* --- YAF_CG_AUTO_UPGRADE_END -- */