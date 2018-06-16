json = {
    encode: function(arr) {
        return JSON.stringify(arr);
    },
    
    decode: function(str) {
        return JSON.parse(str);
    }
};

func = {
    bool: function(str) {
        if (typeof(str) === 'boolean') return str;
        return Boolean(+str);
    },
    
    microtime: function() {
        var date = new Date();
        return date.getTime() / 1000;
    },
    
    fixed: function(num, len) {
        num = parseFloat(num);
        var pow = Math.pow(10, len);
        return (parseInt(num*pow)/pow).toFixed(len);
    }
};