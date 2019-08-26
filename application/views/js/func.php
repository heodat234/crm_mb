function actionPhoneRing(ele) {
    swal({
        title: "@Action@",
        text: `@What do you want to do with this call@?`,
        icon: "warning",
        buttons: {
            cancel: "@Cancel@",
            no: {
              text: "@Reject@",
              value: "CALLEND",
              className: "swal-button--danger",
            },
            ok: {
              text: "@Answer@",
              value: "ANSWER",
              className: "swal-button--success",
            },
        }
    })
    .then((value) => {
        if(value) {
          $.get(ENV.vApi + "ipphone/press", {key: value}, function(res) {notification.show(res.message, res.status ? "success" : "error")});
        }
    })
}