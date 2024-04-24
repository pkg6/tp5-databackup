layui.define(['table', 'form', 'layer'], function (exports) {
    var layer = layui.layer;
    var table = layui.table;
    var $ = layui.jquery;
    var elem_import_table = "elem-import-table"
    var script_backup_table_row_opt = "table-row-opt";

    var import_table_url = document.getElementById(elem_import_table).getAttribute("table-url")
    var download_url = document.getElementById(elem_import_table).getAttribute("download-url")
    var import_url = document.getElementById(elem_import_table).getAttribute("import-url")
    // 渲染
    table.render({
        elem: '#' + elem_import_table,
        url: import_table_url, // 此处为静态模拟数据，实际使用时需换成真实接口
        cols: [[
            {field: 'name', title: '文件名', width: 120},
            {field: 'database', title: '数据库', width: 120},
            {field: 'connection', title: '数据库链接', width: 120},
            {field: 'size', title: '大小', width: 120},
            {fixed: 'right', title: '操作', width: 200, toolbar: '#'+script_backup_table_row_opt}
        ]],
        initSort: { // 设置初始排序
            field: 'experience', // 字段名
            type: 'desc' // 倒序
        },
    });
    //行级事件
    table.on("tool(" + elem_import_table + ")", function (obj) {
        var data = obj.data;
        console.log(data)
        switch (obj.event) {
            case "download":
                layer.msg('确定要下载吗？', {
                    time: 0 // 永不关闭
                    , btn: ['确定', '取消']
                    , yes: function (index) {
                        window.open(download_url + "?filename=" + data.filename, '_blank');
                        layer.close(index);
                    }
                });
                break;
            case "import":
                layer.msg('确定要导入吗？', {
                    time: 0 // 永不关闭
                    , btn: ['确定', '取消']
                    , yes: function (index) {
                        $.ajax({
                            type: "GET",
                            url: import_url,
                            data: {"name": data.name},
                            success: function (ret) {
                                layer.msg(ret.msg)
                            }
                        })
                        layer.close(index);
                    }
                });
                break;
            default:
                layer.alert("无法处理此刻操作")
        }
    })


    exports('import', {});
});