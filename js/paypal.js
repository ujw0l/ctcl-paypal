

document.addEventListener('DOMContentLoaded',()=>{

    let submitButton = document.querySelector('.ctcl-checkout-button'); 
    

    Array.from(document.querySelectorAll('input[name=payment_option]')).map(x=>{

     x.addEventListener('change',(e)=>{

        if(e.target.value == 'ctcl_paypal' &&  e.target.checked == true){

            submitButton.disabled = true;

            let sucessCont = document.querySelector('#paypal-payment-sucess');

            if(sucessCont != null){


              

                submitButton.disabled = false; 
            }


        }else{

            submitButton.disabled = false;

        }
        
     })   

    })
    
        
  

    paypal.Buttons({
        createOrder: function(data, actions) {
            return actions.order.create({
                purchase_units: [{
                    amount: {
                        value: document.querySelector('#ctcl-subtotal-hidden-input').value  // Set the amount to be charged
                    }
                }]
            });
        },
        onApprove: function(data, actions) {
            return actions.order.capture().then(function(details) {


                document.querySelector('#paypal-button-container').innerHTML = '<p  id="paypal-payment-sucess"  >'+ctclPaypalObject.paymentSuccess+'</p>';

                submitButton.disabled = false;
             

                Array.from(document.querySelectorAll('input[name="shipping_option"]')).map(x=>{



                    

                    if(x.checked == true){
                        x.disabled = false;
                    }else{
                        x.disabled = true;
                    }

                })

                submitButton.click();
            });



        },
        onError: function(err) {
            console.error(err);
            document.querySelector('#ctcl-paypal-error').style.dispay = 'block'; 
        },
        style: {
            layout: 'vertical',  // horizontal | vertical
            color: 'blue',       // gold | blue | silver | black
            shape: 'rect',       // pill | rect
            label: 'pay',   // checkout | pay | buynow | paypal | installment
            tagline: false,       // true | false,
            height :40,
        },
    }).render('#paypal-button-container');





})