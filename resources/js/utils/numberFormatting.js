const formatters = {
    IDR: new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
    }),
    USD: new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "USD",
    }),
    SGD: new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "SGD",
    }),
    JPY: new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "JPY",
    }),
};

export const formatCurrency = (currency, price) => {
    if (!price) return "-";
    const formatter = formatters[currency];
    if (!formatter) return "-";
    return formatter.format(parseFloat(price));
};

export const formatNumber = (value) => {
    if (!value) return "0";
    return parseFloat(value).toLocaleString("en-US", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    });
};
