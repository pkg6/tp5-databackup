layui.define(['table', 'form', 'layer'], function (exports) {
    var layer = layui.layer;
    var table = layui.table;
    var $ = layui.jquery;

    function merger(...obj) {
        let res = {};
        let combine = (obj) => {
            for (let prop in obj) {
                if (obj.hasOwnProperty(prop)) {
                    if (Object.prototype.toString.call(obj[prop]) === '[object Object]') {
                        res[prop] = merger(res[prop], obj[prop]);
                    } else {
                        res[prop] = obj[prop];
                    }
                }
            }
        }
        //扩张运算符将两个对象合并到一个数组里因此可以调用length方法
        for (let i = 0; i < obj.length; i++) {
            combine(obj[i]);
        }
        return res;
    }

    function data2tables(data) {
        let tables = [];
        for (let i = 0; i < data.length; i++) {
            tables[i] = data2table(data[i]);
        }
        return tables;
    }

    function data2table(data) {
        return data.name
    }

    function objtables(obj) {
        return data2tables(table.checkStatus(obj.config.id).data);
    }


    var backup = {
        bind: function (elem) {
            this.elem = elem
            // //访问列表的接口
            this.url = document.getElementById(this.elem).getAttribute("lay-url")
            return this
        },
        render: function (options) {
            options = merger(options ? options : {}, {
                elem: '#' + backup.elem,
                url: backup.url,
            })
            table.render(options);
            return this
        },
        on() {
            // 头工具栏事件
            table.on("toolbar(" + this.elem + ")", function (obj) {
                typeof backup.events[obj.event] === "function" && backup.events[obj.event](this, obj);
            });
            table.on("tool(" + this.elem + ")", function (obj) {
                typeof backup.events[obj.event] === "function" && backup.events[obj.event](this, obj);
            })
        },
        events: {
            import: function (elem, obj) {
                location.href = elem.getAttribute("lay-url")
            },
            backups: function (elem, obj) {
                var tables = objtables(obj)
                var step1_url = elem.getAttribute("step1-url")
                var step2_url = elem.getAttribute("step2-url")
                var step3_url = elem.getAttribute("step3-url")
                var process_id = elem.getAttribute("backup-process-id")
                backup.events.step1(step1_url, tables, function (ret1) {
                    elem.classList.add('layui-btn-disabled')
                    backup.events.process_msg(process_id, ret1.msg)
                    backup.events.step2(step2_url, ret1.data.index, ret1.data.page, function (ret2) {
                        backup.events.process_msg(process_id, "当前正在备份的表:" + ret2.data.table)
                        backup.events.step3(step3_url, ret2, function (ret3) {
                            backup.events.process_msg(process_id, "备份完毕")
                            elem.classList.remove('layui-btn-disabled')
                        })
                    })
                })
            },
            optimize: function (elem, obj) {
                var url = elem.getAttribute("lay-url")
                var tables = data2table(obj.data)
                backup.events.post(url, tables)
            },
            optimizes: function (elem, obj) {
                var url = elem.getAttribute("lay-url")
                var tables = objtables(obj)
                backup.events.post(url, tables)
            },
            repair: function (elem, obj) {
                var url = elem.getAttribute("lay-url")
                var tables = data2table(obj.data)
                backup.events.post(url, tables)
            },
            repairs: function (elem, obj) {
                var url = elem.getAttribute("lay-url")
                var tables = objtables(obj)
                backup.events.post(url, tables)
            },
            post: function (url, tables) {
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: {"tables": tables},
                    success: function (ret) {
                        layer.msg(ret.msg)
                    }
                })
            },
            process_msg: function (process_id, text) {
                if (process_id === null) {
                    return
                }
                $("#" + process_id).html(text)
            },
            step1: function (url, table, callback) {
                $.ajax({
                    type: "POST",
                    url: url,
                    data: {"tables": table},
                    success: function (ret) {
                        if (ret.code === 0) {
                            typeof callback === "function" && callback(ret);
                        } else {
                            layer.msg(ret.msg)
                        }
                    }
                })
            },
            step2: function (url, index, page, callback) {
                $.ajax({
                    type: "GET",
                    async: true,
                    url: url,
                    data: {"index": index, "page": page},
                    success: function (ret) {
                        if (ret.code === 0) {
                            if (ret.data.page >= 0) {
                                backup.events.step2(url, ret.data.index, ret.data.page, callback)
                            }
                            typeof callback === "function" && callback(ret);
                        } else {
                            layer.msg(ret.msg)
                        }
                    }
                })
            },
            step3: function (url, ret2, callback) {
                console.log("当前正在备份的表:" + ret2.data.table)
                if (ret2.data.page === -1) {
                    $.ajax({
                        type: "GET",
                        url: url,
                        success: function (ret) {
                            typeof callback === "function" && callback(ret);
                        }
                    })
                }
            },

        }
    }

    var imports = {
        bind: function (elem) {
            this.elem = elem
            //访问列表的接口
            this.url = document.getElementById(this.elem).getAttribute("lay-url")
            return this
        },
        render: function (options) {
            options = merger(options ? options : {}, {
                elem: '#' + imports.elem,
                url: imports.url,
            })
            table.render(options);
            return this
        },
        on: function () {
            // 头工具栏事件
            table.on("toolbar(" + this.elem + ")", function (obj) {
                typeof imports.events[obj.event] === "function" && imports.events[obj.event](this, obj);
            });
            table.on("tool(" + this.elem + ")", function (obj) {
                typeof imports.events[obj.event] === "function" && imports.events[obj.event](this, obj);
            })
        },
        events: {
            backup: function (elem, obj) {
                location.href = elem.getAttribute("lay-url")
            },
            delete: function (elem, obj) {
                var url = elem.getAttribute("lay-url")
                var data = obj.data;
                layer.msg('确定要删除吗？', {
                    time: 0
                    , btn: ['确定', '取消']
                    , yes: function (index) {
                        $.ajax({
                            type: "GET",
                            url: url,
                            data: {"filename": data.name},
                            success: function (ret) {
                                obj.del()
                                layer.msg(ret.msg)
                            }
                        })
                        layer.close(index);
                    }
                });
            },
            deletes: function (elem, obj) {
                var data = table.checkStatus(obj.config.id).data;
                var url = elem.getAttribute("lay-url")
                var names = []
                var filenames = []
                data.forEach(element => {
                    names.push(element.name)
                    filenames.push(element.filename)
                });

                layer.msg('确定要删除吗？' + names.join(","), {
                    time: 0
                    , btn: ['确定', '取消']
                    , yes: function (index) {
                        filenames.forEach(file => {
                            $.ajax({
                                type: "GET",
                                url: url,
                                async: true,
                                data: {"filename": file},
                                success: function (ret) {
                                    // obj.del()
                                    layer.msg(ret.msg)
                                }
                            })
                        })
                        layer.close(index);
                    }
                });
            },
            download: function (elem, obj) {
                var data = obj.data;
                var url = elem.getAttribute("lay-url")
                layer.msg('确定要下载吗？', {
                    time: 0
                    , btn: ['确定', '取消']
                    , yes: function (index) {
                        window.open(url + "?filename=" + data.filename, '_blank');
                        layer.close(index);
                    }
                });
            },
            import: function (elem, obj) {
                var url = elem.getAttribute("lay-url")
                var data = obj.data;
                layer.msg('确定要导入吗？', {
                    time: 0 // 永不关闭
                    , btn: ['确定', '取消']
                    , yes: function (index) {
                        $.ajax({
                            type: "GET",
                            url: url,
                            data: {"name": data.name},
                            success: function (ret) {
                                layer.msg(ret.msg)
                            }
                        })
                        layer.close(index);
                    }
                });
            },
        }

    }
    exports('tp5er', {backup: backup, imports: imports});
});
