// This is included with the Parsley library itself,
// thus there is no use in adding it to your project.


Parsley.addMessages('en', {
    defaultMessage: uni_parsley_loc.defaultMessage,
    type: {
        email:        uni_parsley_loc.type_email,
        url:          uni_parsley_loc.type_url,
        number:       uni_parsley_loc.type_number,
        integer:      uni_parsley_loc.type_integer,
        digits:       uni_parsley_loc.type_digits,
        alphanum:     uni_parsley_loc.type_alphanum
    },
    notblank:       uni_parsley_loc.notblank,
    required:       uni_parsley_loc.required,
    pattern:        uni_parsley_loc.pattern,
    min:            uni_parsley_loc.min,
    max:            uni_parsley_loc.max,
    range:          uni_parsley_loc.range,
    minlength:      uni_parsley_loc.minlength,
    maxlength:      uni_parsley_loc.maxlength,
    length:         uni_parsley_loc.length,
    mincheck:       uni_parsley_loc.mincheck,
    maxcheck:       uni_parsley_loc.maxcheck,
    check:          uni_parsley_loc.check,
    equalto:        uni_parsley_loc.equalto
});

Parsley.addMessages('en', {
    dateiso:  uni_parsley_loc.dateiso,
    minwords: uni_parsley_loc.minwords,
    maxwords: uni_parsley_loc.maxwords,
    words:    uni_parsley_loc.words,
    gt:       uni_parsley_loc.gt,
    gte:      uni_parsley_loc.gte,
    lt:       uni_parsley_loc.lt,
    lte:      uni_parsley_loc.lte,
    notequalto: uni_parsley_loc.notequalto
});

Parsley.setLocale('en');
