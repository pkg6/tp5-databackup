layui.define(['table', 'form', 'layer'], function (exports) {
    var layer = layui.layer;
    var table = layui.table;
    var $ = layui.jquery;
    //单行操作节点
    var script_backup_table_row_opt = "table-row-opt";
    //多行操作
    var script_backup_table_opt = "table-opt"
    var elem_backup_process = "backup-process"
    //表格渲染节点
    var elem_backup_table = "elem-backup-tables";
    //访问列表的接口
    var backup_table_url = document.getElementById(elem_backup_table).getAttribute("table-url")
    var backup_step1_url = document.getElementById(elem_backup_table).getAttribute("step1-url")
    var backup_step2_url = document.getElementById(elem_backup_table).getAttribute("step2-url")
    var backup_step3_url = document.getElementById(elem_backup_table).getAttribute("step3-url")
    var repair_url = document.getElementById(elem_backup_table).getAttribute("repair-url")
    var optimize_url = document.getElementById(elem_backup_table).getAttribute("optimize-url")

    console.log(backup_table_url, backup_step1_url, backup_step2_url, backup_step3_url, repair_url, optimize_url)
    // 渲染
    table.render({
        elem: '#' + elem_backup_table,
        url: backup_table_url, // 此处为静态模拟数据，实际使用时需换成真实接口
        cols: [[
            {type: 'checkbox', fixed: 'left'},
            {field: 'name', title: '表名', width: 120},
            {field: 'engine', title: '引擎', width: 120},
            {field: 'rows', title: '数据量', width: 120},
            {field: 'data_length', title: '数据大小', width: 120},
            {field: 'comment', title: '表注释', width: 120},
            {field: 'create_time', title: '创建时间', width: 180},
            {fixed: 'right', title: '操作', width: 200, toolbar: '#' + script_backup_table_row_opt}
        ]],
        toolbar: '#' + script_backup_table_opt,
        initSort: {
            field: 'experience',
            type: 'desc'
        },
    });
    // 头工具栏事件
    table.on("toolbar(" + elem_backup_table + ")", function (obj) {
        //获取选中状态的表
        var checked_data = table.checkStatus(obj.config.id).data;
        var tables = data2tables(checked_data);
        switch (obj.event) {
            case "optimizes":
                ajaxTable("POST", optimize_url, tables)
                break;
            case "repairs":
                ajaxTable("POST", repair_url, tables)
                break;
            case "backups":
                backupAjax(tables);
                break;

            default:
                layer.alert("无法处理此刻操作")
        }
    });
    //行级事件
    table.on("tool(" + elem_backup_table + ")", function (obj) {
        var data = obj.data;
        var tables = data2table(data);
        switch (obj.event) {
            case "optimize":
                ajaxTable("POST", optimize_url, tables)
                break;
            case "repair":
                ajaxTable("POST", repair_url, tables)
                break;
            default:
                layer.alert("无法处理此刻操作")
        }
    })

    //备份
    function backupAjax(table) {
        $.ajax({
            type: "POST",
            url: backup_step1_url,
            data: {"tables": table},
            success: function (ret) {
                if (ret.code === 0) {
                    showmsg(ret.msg)
                    backupAjax2(ret.data.index, ret.data.page)
                } else {
                    layer.msg(ret.msg)
                }
            }
        })
    }

    //递归备份数据
    function backupAjax2(index, page) {
        $.ajax({
            type: "GET",
            url: backup_step2_url,
            data: {"index": index, "page": page},
            success: function (ret) {
                if (ret.code === 0) {
                    showmsg("当前正在备份的表:" + ret.data.table)
                    if (ret.data.page >= 0) {
                        //TODO 备份表按钮是不可点击
                        backupAjax2(ret.data.index, ret.data.page)
                    } else {
                        backupAjax3()
                    }
                } else {
                    layer.msg(ret.msg)
                }
            }
        })
    }

    //清理缓存
    function backupAjax3() {
        $.ajax({
            type: "GET",
            url: backup_step3_url,
            success: function (ret) {
                showmsg("备份完毕")
                layer.msg("备份完毕")
            }
        })
    }

    function showmsg(msg) {
        $("#" + elem_backup_process).html(msg)
    }

    function ajaxTable(method, path, table) {
        $.ajax({
            type: method,
            url: path,
            data: {"tables": table},
            success: function (ret) {
                layer.msg(ret.msg)
            }
        })
    }

    //(批量操作)将layui选中节点对象提取table
    function data2tables(data) {
        let tables = [];
        for (let i = 0; i < data.length; i++) {
            tables[i] = data2table(data[i]);
        }
        return tables;
    }

    //（单行操作）将layui节点对象提取table
    function data2table(data) {
        return data.name
    }

    exports('backup', {});
});