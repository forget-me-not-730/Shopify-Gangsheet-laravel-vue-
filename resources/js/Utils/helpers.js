export const getProductTypeLabel = (product) => {
    switch (Number(product.type ?? product.artBoardType)) {
        case 1:
            return 'Gang Sheet';
        case 2:
            return 'Sticker';
        case 3:
            return 'Laser';
        case 4:
            return 'Business Card';
        case 5:
            return 'Banner';
        case 6:
            return 'Rolling Gang Sheet';
        default:
            return 'Gang Sheet';
    }
}