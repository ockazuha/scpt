json = {
    encode: function(arr) {
        return JSON.stringify(arr);
    },
    
    decode: function(str) {
        return JSON.parse(str);
    }
};
