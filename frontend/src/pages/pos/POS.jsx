import { useState, useEffect, useCallback, useRef } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../services/api';
import { formatCurrency } from '../../utils/formatters';
import toast from 'react-hot-toast';
import Swal from 'sweetalert2';

export default function POS() {
  const [search, setSearch] = useState('');
  const [cart, setCart] = useState([]);
  const [selectedCustomer, setSelectedCustomer] = useState(null);
  const [showPayment, setShowPayment] = useState(false);
  const [paymentMethod, setPaymentMethod] = useState('cash');
  const [amountPaid, setAmountPaid] = useState('');
  const [discount, setDiscount] = useState(0);
  const [discountType, setDiscountType] = useState('fixed');
  const [isProcessing, setIsProcessing] = useState(false);
  const [heldSales, setHeldSales] = useState([]);
  const searchRef = useRef(null);
  const queryClient = useQueryClient();

  const { data: products } = useQuery({
    queryKey: ['pos-products', search],
    queryFn: async () => {
      const res = await api.get('/products', { params: { search, per_page: 50, status: 'active' } });
      const result = res.data;
      return result?.data || result?.items || [];
    },
  });

  const { data: customers } = useQuery({
    queryKey: ['pos-customers'],
    queryFn: async () => {
      const res = await api.get('/customers', { params: { per_page: 100 } });
      const result = res.data;
      return result?.data || result?.items || [];
    },
  });

  const { data: companySettings } = useQuery({
    queryKey: ['settings-company'],
    queryFn: () => api.get('/settings/company').then(res => res.data.data || res.data),
  });

  const subtotal = cart.reduce((sum, item) => sum + item.quantity * item.selling_price, 0);
  const totalDiscount = discountType === 'percentage' ? subtotal * (discount / 100) : discount;
  const taxableAmount = subtotal - totalDiscount;
  const taxRate = companySettings?.tax_rate ?? 7.5;
  const taxAmount = taxableAmount * (taxRate / 100);
  const total = taxableAmount + taxAmount;

  const addToCart = useCallback((product) => {
    setCart((prev) => {
      const existing = prev.find((item) => item.product_id === product.id);
      if (existing) {
        return prev.map((item) =>
          item.product_id === product.id
            ? { ...item, quantity: item.quantity + 1 }
            : item
        );
      }
      return [
        ...prev,
        {
          product_id: product.id,
          name: product.name,
          sku: product.sku,
          selling_price: parseFloat(product.selling_price),
          quantity: 1,
          stock: product.stock_quantity || 0,
          tax_rate: parseFloat(product.tax_rate || 0),
        },
      ];
    });
    searchRef.current?.focus();
  }, []);

  const updateQuantity = useCallback((productId, qty) => {
    if (qty < 1) return;
    setCart((prev) =>
      prev.map((item) =>
        item.product_id === productId ? { ...item, quantity: qty } : item
      )
    );
  }, []);

  const removeFromCart = useCallback((productId) => {
    setCart((prev) => prev.filter((item) => item.product_id !== productId));
  }, []);

  const clearCart = useCallback(() => {
    setCart([]);
    setSelectedCustomer(null);
    setDiscount(0);
  }, []);

  const holdSale = useCallback(() => {
    if (cart.length === 0) return toast.error('Cart is empty');
    const holdRef = `HOLD-${Date.now().toString(36).toUpperCase()}`;
    setHeldSales((prev) => [...prev, { ref: holdRef, cart: [...cart], customer: selectedCustomer, discount, discountType }]);
    clearCart();
    toast.success(`Sale held as ${holdRef}`);
  }, [cart, selectedCustomer, discount, discountType, clearCart]);

  const resumeSale = useCallback((held) => {
    setCart(held.cart);
    setSelectedCustomer(held.customer);
    setDiscount(held.discount);
    setDiscountType(held.discountType);
    setHeldSales((prev) => prev.filter((h) => h.ref !== held.ref));
    toast.success('Sale resumed');
  }, []);

  const processSale = useCallback(async () => {
    if (cart.length === 0) return toast.error('Cart is empty');
    if (amountPaid && parseFloat(amountPaid) < total) {
      return toast.error('Amount paid is less than total');
    }

    setIsProcessing(true);
    try {
      const saleData = {
        customer_id: selectedCustomer?.id || null,
        warehouse_id: 1,
        branch_id: 1,
        items: cart.map((item) => ({
          product_id: item.product_id,
          quantity: item.quantity,
          unit_price: item.selling_price,
          tax_rate: item.tax_rate,
        })),
        discount_amount: totalDiscount,
        discount_type: discountType,
        payment_method: paymentMethod,
        paid_amount: parseFloat(amountPaid) || total,
        notes: '',
      };

      await api.post('/sales', saleData);
      await queryClient.invalidateQueries({ queryKey: ['dashboard'] });

      Swal.fire({
        icon: 'success',
        title: 'Sale Completed!',
        text: `Total: ${formatCurrency(total)}`,
        timer: 2000,
        showConfirmButton: false,
        background: '#16213E',
        color: '#F8FAFC',
      });

      clearCart();
      setShowPayment(false);
      setAmountPaid('');
      setPaymentMethod('cash');
    } catch (error) {
      toast.error(error.message || 'Failed to process sale');
    } finally {
      setIsProcessing(false);
    }
  }, [cart, selectedCustomer, total, totalDiscount, discountType, paymentMethod, amountPaid, clearCart, queryClient]);

  useEffect(() => {
    const handleKeyDown = (e) => {
      if (e.key === 'F2') {
        e.preventDefault();
        searchRef.current?.focus();
      }
      if (e.key === 'F4') {
        e.preventDefault();
        if (cart.length > 0) setShowPayment(true);
      }
      if (e.key === 'F5') {
        e.preventDefault();
        holdSale();
      }
      if (e.key === 'Escape') {
        setShowPayment(false);
      }
    };
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [cart.length, holdSale]);

  return (
    <div style={styles.posLayout}>
      {/* Left: Products */}
      <div style={styles.productsPanel}>
        <div style={styles.searchSection}>
          <div style={{ display: 'flex', gap: '0.75rem', marginBottom: '0.75rem' }}>
            <div style={{ flex: 1, position: 'relative' }}>
              <i className="bi bi-search" style={{ position: 'absolute', left: 14, top: '50%', transform: 'translateY(-50%)', color: '#64748B' }} />
              <input
                ref={searchRef}
                type="text"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                placeholder="Search products... (F2)"
                style={styles.searchInput}
                autoFocus
              />
            </div>
            <button onClick={holdSale} style={styles.holdBtn} title="Hold Sale (F5)">
              <i className="bi bi-pause-circle" /> Hold
            </button>
          </div>
          <div style={styles.shortcutHints}>
            <span style={styles.shortcut}><kbd>F2</kbd> Search</span>
            <span style={styles.shortcut}><kbd>F4</kbd> Pay</span>
            <span style={styles.shortcut}><kbd>F5</kbd> Hold</span>
            <span style={styles.shortcut}><kbd>Esc</kbd> Close</span>
          </div>
        </div>

        <div style={styles.productGrid}>
          {(products || []).map((product) => (
            <div
              key={product.id}
              onClick={() => addToCart(product)}
              style={styles.productCard}
              title={`Click to add ${product.name}`}
            >
              <div style={styles.productImage}>
                <i className="bi bi-box-seam" style={{ fontSize: '1.5rem', color: '#3B82F6' }} />
              </div>
              <div style={styles.productInfo}>
                <p style={styles.productName}>{product.name}</p>
                <p style={styles.productSku}>{product.sku}</p>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                  <span style={styles.productPrice}>{formatCurrency(product.selling_price)}</span>
                  <span style={{ ...styles.stockBadge, color: (product.stock_quantity || 0) < 5 ? '#EF4444' : '#22C55E' }}>
                    {product.stock_quantity || 0} in stock
                  </span>
                </div>
              </div>
            </div>
          ))}
          {(!products || products.length === 0) && (
            <div style={{ gridColumn: '1 / -1', textAlign: 'center', padding: '3rem', color: '#64748B' }}>
              <i className="bi bi-search" style={{ fontSize: '2rem', marginBottom: '0.75rem', display: 'block' }} />
              <p>No products found. Try a different search term.</p>
            </div>
          )}
        </div>

        {/* Held Sales */}
        {heldSales.length > 0 && (
          <div style={styles.heldSection}>
            <h6 style={{ margin: '0 0 0.5rem', color: '#F59E0B', fontSize: '0.8125rem' }}>
              <i className="bi bi-pause-circle" /> Held Sales ({heldSales.length})
            </h6>
            <div style={{ display: 'flex', gap: '0.5rem', flexWrap: 'wrap' }}>
              {heldSales.map((held) => (
                <button key={held.ref} onClick={() => resumeSale(held)} style={styles.heldBtn}>
                  {held.ref} ({held.cart.length} items)
                </button>
              ))}
            </div>
          </div>
        )}
      </div>

      {/* Right: Cart */}
      <div style={styles.cartPanel}>
        <div style={styles.cartHeader}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
            <i className="bi bi-cart3" style={{ fontSize: '1.125rem' }} />
            <span style={{ fontWeight: 600, fontSize: '0.9375rem' }}>Cart</span>
            <span style={styles.cartCount}>{cart.reduce((s, i) => s + i.quantity, 0)}</span>
          </div>
          {cart.length > 0 && (
            <button onClick={clearCart} style={{ ...styles.clearBtn }}>
              <i className="bi bi-trash" /> Clear
            </button>
          )}
        </div>

        {/* Customer Selection */}
        <div style={styles.customerSection}>
          <select
            value={selectedCustomer?.id || ''}
            onChange={(e) => {
              const c = (customers || []).find((c) => c.id === parseInt(e.target.value));
              setSelectedCustomer(c || null);
            }}
            style={styles.customerSelect}
          >
            <option value="">Walk-in Customer</option>
            {(customers || []).map((c) => (
              <option key={c.id} value={c.id}>{c.first_name} {c.last_name}</option>
            ))}
          </select>
        </div>

        {/* Cart Items */}
        <div style={styles.cartItems}>
          {cart.length === 0 ? (
            <div style={{ textAlign: 'center', padding: '2rem', color: '#64748B' }}>
              <i className="bi bi-cart-x" style={{ fontSize: '2rem', marginBottom: '0.5rem', display: 'block' }} />
              <p style={{ fontSize: '0.8125rem' }}>Cart is empty</p>
              <p style={{ fontSize: '0.75rem' }}>Click products or scan barcodes</p>
            </div>
          ) : (
            cart.map((item) => (
              <div key={item.product_id} style={styles.cartItem}>
                <div style={{ flex: 1, minWidth: 0 }}>
                  <p style={{ fontSize: '0.8125rem', fontWeight: 500, color: '#F8FAFC', margin: 0, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                    {item.name}
                  </p>
                  <p style={{ fontSize: '0.6875rem', color: '#64748B', margin: 0 }}>{formatCurrency(item.selling_price)} each</p>
                </div>
                <div style={styles.qtyControl}>
                  <button onClick={() => updateQuantity(item.product_id, item.quantity - 1)} style={styles.qtyBtn}>-</button>
                  <span style={{ minWidth: 28, textAlign: 'center', fontSize: '0.8125rem', fontWeight: 600 }}>{item.quantity}</span>
                  <button onClick={() => updateQuantity(item.product_id, item.quantity + 1)} style={styles.qtyBtn}>+</button>
                </div>
                <div style={{ textAlign: 'right', minWidth: 80 }}>
                  <p style={{ fontSize: '0.8125rem', fontWeight: 600, color: '#22C55E', margin: 0 }}>{formatCurrency(item.selling_price * item.quantity)}</p>
                  <button onClick={() => removeFromCart(item.product_id)} style={{ ...styles.removeBtn }}>
                    <i className="bi bi-x" />
                  </button>
                </div>
              </div>
            ))
          )}
        </div>

        {/* Discount */}
        {cart.length > 0 && (
          <div style={styles.discountRow}>
            <input
              type="number"
              placeholder="Discount"
              value={discount}
              onChange={(e) => setDiscount(parseFloat(e.target.value) || 0)}
              style={{ ...styles.discountInput, flex: 1 }}
            />
            <select
              value={discountType}
              onChange={(e) => setDiscountType(e.target.value)}
              style={styles.discountInput}
            >
              <option value="fixed">₦</option>
              <option value="percentage">%</option>
            </select>
          </div>
        )}

        {/* Totals */}
        <div style={styles.totals}>
          <div style={styles.totalRow}>
            <span>Subtotal</span>
            <span>{formatCurrency(subtotal)}</span>
          </div>
          {totalDiscount > 0 && (
            <div style={{ ...styles.totalRow, color: '#EF4444' }}>
              <span>Discount</span>
              <span>-{formatCurrency(totalDiscount)}</span>
            </div>
          )}
          <div style={styles.totalRow}>
            <span>Tax ({taxRate}%)</span>
            <span>{formatCurrency(taxAmount)}</span>
          </div>
          <div style={styles.totalRowTotal}>
            <span>Total</span>
            <span>{formatCurrency(total)}</span>
          </div>
        </div>

        {/* Payment Buttons */}
        <div style={styles.paymentButtons}>
          <button
            onClick={() => setShowPayment(true)}
            disabled={cart.length === 0}
            style={{
              ...styles.payBtn,
              opacity: cart.length === 0 ? 0.5 : 1,
            }}
          >
            <i className="bi bi-check-circle" />
            Pay Now
          </button>
        </div>
      </div>

      {/* Payment Modal */}
      {showPayment && (
        <div style={styles.modalOverlay}>
          <div style={styles.modal}>
            <div style={styles.modalHeader}>
              <h3 style={{ margin: 0, fontSize: '1.125rem' }}>Process Payment</h3>
              <button onClick={() => setShowPayment(false)} style={styles.modalClose}>
                <i className="bi bi-x-lg" />
              </button>
            </div>
            <div style={styles.modalBody}>
              <div style={styles.totalDisplay}>
                <span style={{ fontSize: '0.875rem', color: '#94A3B8' }}>Amount Due</span>
                <span style={{ fontSize: '2rem', fontWeight: 700, color: '#22C55E' }}>{formatCurrency(total)}</span>
              </div>

              <div style={{ marginBottom: '1rem' }}>
                <label style={styles.fieldLabel}>Payment Method</label>
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: '0.5rem' }}>
                  {[
                    { id: 'cash', icon: 'bi-cash', label: 'Cash' },
                    { id: 'card', icon: 'bi-credit-card', label: 'Card' },
                    { id: 'transfer', icon: 'bi-bank', label: 'Transfer' },
                    { id: 'wallet', icon: 'bi-phone', label: 'Wallet' },
                  ].map((m) => (
                    <button
                      key={m.id}
                      onClick={() => setPaymentMethod(m.id)}
                      style={{
                        ...styles.payMethodBtn,
                        borderColor: paymentMethod === m.id ? '#2563EB' : '#334155',
                        background: paymentMethod === m.id ? 'rgba(37, 99, 235, 0.15)' : 'transparent',
                        color: paymentMethod === m.id ? '#3B82F6' : '#94A3B8',
                      }}
                    >
                      <i className={`bi ${m.icon}`} />
                      <span style={{ fontSize: '0.75rem' }}>{m.label}</span>
                    </button>
                  ))}
                </div>
              </div>

              <div style={{ marginBottom: '1rem' }}>
                <label style={styles.fieldLabel}>Amount Paid</label>
                <input
                  type="number"
                  value={amountPaid}
                  onChange={(e) => setAmountPaid(e.target.value)}
                  placeholder={total.toFixed(2)}
                  style={styles.modalInput}
                />
              </div>

              {amountPaid && parseFloat(amountPaid) > 0 && (
                <div style={styles.changeDisplay}>
                  <span>Change</span>
                  <span style={{ color: '#22C55E', fontWeight: 700 }}>
                    {formatCurrency(Math.max(0, parseFloat(amountPaid) - total))}
                  </span>
                </div>
              )}
            </div>
            <div style={styles.modalFooter}>
              <button onClick={() => setShowPayment(false)} style={styles.cancelBtn}>
                Cancel (Esc)
              </button>
              <button
                onClick={processSale}
                disabled={isProcessing}
                style={{
                  ...styles.completeBtn,
                  opacity: isProcessing ? 0.7 : 1,
                }}
              >
                {isProcessing ? (
                  <><span style={styles.spinner} /> Processing...</>
                ) : (
                  <><i className="bi bi-check-lg" /> Complete Sale</>
                )}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

const styles = {
  posLayout: {
    display: 'flex',
    height: 'calc(100vh - 64px)',
    margin: '-1.5rem',
    background: '#0F172A',
    overflow: 'hidden',
  },
  productsPanel: {
    flex: 1,
    display: 'flex',
    flexDirection: 'column',
    borderRight: '1px solid #1E293B',
    overflow: 'hidden',
  },
  searchSection: {
    padding: '1rem 1.25rem',
    borderBottom: '1px solid #1E293B',
    background: '#0B1120',
  },
  searchInput: {
    width: '100%',
    padding: '0.625rem 1rem 0.625rem 2.5rem',
    background: '#1E293B',
    border: '1px solid #334155',
    borderRadius: 10,
    color: '#F8FAFC',
    fontSize: '0.875rem',
    outline: 'none',
  },
  holdBtn: {
    padding: '0.625rem 1rem',
    background: 'rgba(245, 158, 11, 0.1)',
    border: '1px solid rgba(245, 158, 11, 0.3)',
    borderRadius: 10,
    color: '#F59E0B',
    fontSize: '0.8125rem',
    fontWeight: 500,
    cursor: 'pointer',
    display: 'flex',
    alignItems: 'center',
    gap: '0.375rem',
    whiteSpace: 'nowrap',
  },
  shortcutHints: {
    display: 'flex',
    gap: '0.75rem',
  },
  shortcut: {
    fontSize: '0.6875rem',
    color: '#64748B',
  },
  productGrid: {
    flex: 1,
    overflow: 'auto',
    padding: '1rem 1.25rem',
    display: 'grid',
    gridTemplateColumns: 'repeat(auto-fill, minmax(180px, 1fr))',
    gap: '0.75rem',
    alignContent: 'start',
  },
  productCard: {
    background: '#16213E',
    border: '1px solid #1E293B',
    borderRadius: 12,
    padding: '0.875rem',
    cursor: 'pointer',
    transition: 'all 0.15s ease',
  },
  productImage: {
    height: 64,
    background: 'rgba(37, 99, 235, 0.1)',
    borderRadius: 8,
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: '0.625rem',
  },
  productInfo: {},
  productName: {
    fontSize: '0.8125rem',
    fontWeight: 600,
    color: '#F8FAFC',
    margin: '0 0 0.125rem',
    overflow: 'hidden',
    textOverflow: 'ellipsis',
    whiteSpace: 'nowrap',
  },
  productSku: {
    fontSize: '0.6875rem',
    color: '#64748B',
    margin: '0 0 0.375rem',
  },
  productPrice: {
    fontSize: '0.875rem',
    fontWeight: 700,
    color: '#22C55E',
  },
  stockBadge: {
    fontSize: '0.625rem',
    fontWeight: 500,
  },
  heldSection: {
    padding: '0.75rem 1.25rem',
    borderTop: '1px solid #1E293B',
    background: '#0B1120',
  },
  heldBtn: {
    padding: '0.375rem 0.75rem',
    background: 'rgba(245, 158, 11, 0.1)',
    border: '1px solid rgba(245, 158, 11, 0.3)',
    borderRadius: 6,
    color: '#F59E0B',
    fontSize: '0.6875rem',
    cursor: 'pointer',
  },
  cartPanel: {
    width: 380,
    display: 'flex',
    flexDirection: 'column',
    background: '#0B1120',
  },
  cartHeader: {
    padding: '0.875rem 1.25rem',
    borderBottom: '1px solid #1E293B',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'space-between',
    color: '#F8FAFC',
  },
  cartCount: {
    background: '#2563EB',
    borderRadius: 6,
    padding: '0.125rem 0.5rem',
    fontSize: '0.6875rem',
    fontWeight: 600,
  },
  clearBtn: {
    padding: '0.375rem 0.75rem',
    background: 'rgba(239, 68, 68, 0.1)',
    border: '1px solid rgba(239, 68, 68, 0.3)',
    borderRadius: 6,
    color: '#EF4444',
    fontSize: '0.75rem',
    cursor: 'pointer',
    display: 'flex',
    alignItems: 'center',
    gap: '0.25rem',
  },
  customerSection: {
    padding: '0.625rem 1.25rem',
    borderBottom: '1px solid #1E293B',
  },
  customerSelect: {
    width: '100%',
    padding: '0.5rem 0.75rem',
    background: '#1E293B',
    border: '1px solid #334155',
    borderRadius: 8,
    color: '#F8FAFC',
    fontSize: '0.8125rem',
    outline: 'none',
  },
  cartItems: {
    flex: 1,
    overflow: 'auto',
    padding: '0.5rem 0',
  },
  cartItem: {
    display: 'flex',
    alignItems: 'center',
    gap: '0.625rem',
    padding: '0.625rem 1.25rem',
    borderBottom: '1px solid #1E293B',
  },
  qtyControl: {
    display: 'flex',
    alignItems: 'center',
    gap: '0.25rem',
    background: '#1E293B',
    borderRadius: 6,
    padding: '0.125rem',
  },
  qtyBtn: {
    width: 26,
    height: 26,
    borderRadius: 4,
    border: 'none',
    background: '#334155',
    color: '#F8FAFC',
    fontSize: '0.8125rem',
    fontWeight: 600,
    cursor: 'pointer',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
  },
  removeBtn: {
    background: 'none',
    border: 'none',
    color: '#EF4444',
    cursor: 'pointer',
    fontSize: '0.75rem',
    padding: '2px',
  },
  discountRow: {
    display: 'flex',
    gap: '0.5rem',
    padding: '0.625rem 1.25rem',
    borderTop: '1px solid #1E293B',
  },
  discountInput: {
    padding: '0.5rem 0.75rem',
    background: '#1E293B',
    border: '1px solid #334155',
    borderRadius: 6,
    color: '#F8FAFC',
    fontSize: '0.8125rem',
    outline: 'none',
  },
  totals: {
    padding: '0.75rem 1.25rem',
    borderTop: '1px solid #1E293B',
  },
  totalRow: {
    display: 'flex',
    justifyContent: 'space-between',
    padding: '0.25rem 0',
    fontSize: '0.8125rem',
    color: '#94A3B8',
  },
  totalRowTotal: {
    display: 'flex',
    justifyContent: 'space-between',
    padding: '0.5rem 0 0',
    fontSize: '1.125rem',
    fontWeight: 700,
    color: '#F8FAFC',
    borderTop: '1px solid #334155',
    marginTop: '0.25rem',
  },
  paymentButtons: {
    padding: '0.75rem 1.25rem 1rem',
  },
  payBtn: {
    width: '100%',
    padding: '0.875rem',
    background: 'linear-gradient(135deg, #22C55E, #16A34A)',
    border: 'none',
    borderRadius: 10,
    color: 'white',
    fontSize: '0.9375rem',
    fontWeight: 600,
    cursor: 'pointer',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    gap: '0.5rem',
  },
  modalOverlay: {
    position: 'fixed',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    background: 'rgba(0,0,0,0.7)',
    backdropFilter: 'blur(4px)',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    zIndex: 9999,
  },
  modal: {
    width: 440,
    background: '#16213E',
    borderRadius: 16,
    border: '1px solid #334155',
    boxShadow: '0 25px 50px rgba(0,0,0,0.5)',
    overflow: 'hidden',
  },
  modalHeader: {
    padding: '1rem 1.5rem',
    borderBottom: '1px solid #1E293B',
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'center',
    color: '#F8FAFC',
  },
  modalClose: {
    background: 'none',
    border: 'none',
    color: '#64748B',
    cursor: 'pointer',
    fontSize: '1rem',
  },
  modalBody: {
    padding: '1.5rem',
  },
  totalDisplay: {
    textAlign: 'center',
    marginBottom: '1.5rem',
    padding: '1rem',
    background: 'rgba(34, 197, 94, 0.08)',
    borderRadius: 12,
    border: '1px solid rgba(34, 197, 94, 0.2)',
  },
  fieldLabel: {
    display: 'block',
    fontSize: '0.8125rem',
    fontWeight: 500,
    color: '#CBD5E1',
    marginBottom: '0.5rem',
  },
  payMethodBtn: {
    padding: '0.75rem',
    background: 'transparent',
    border: '1px solid #334155',
    borderRadius: 8,
    cursor: 'pointer',
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'center',
    gap: '0.25rem',
    fontSize: '1.125rem',
  },
  modalInput: {
    width: '100%',
    padding: '0.75rem 1rem',
    background: '#1E293B',
    border: '1px solid #334155',
    borderRadius: 8,
    color: '#F8FAFC',
    fontSize: '1.125rem',
    fontWeight: 600,
    outline: 'none',
  },
  changeDisplay: {
    display: 'flex',
    justifyContent: 'space-between',
    padding: '0.75rem 1rem',
    background: 'rgba(34, 197, 94, 0.08)',
    borderRadius: 8,
    fontSize: '1rem',
    color: '#CBD5E1',
  },
  modalFooter: {
    padding: '1rem 1.5rem',
    borderTop: '1px solid #1E293B',
    display: 'flex',
    gap: '0.75rem',
    justifyContent: 'flex-end',
  },
  cancelBtn: {
    padding: '0.625rem 1.25rem',
    background: '#1E293B',
    border: '1px solid #334155',
    borderRadius: 8,
    color: '#CBD5E1',
    fontSize: '0.875rem',
    cursor: 'pointer',
  },
  completeBtn: {
    padding: '0.625rem 1.5rem',
    background: 'linear-gradient(135deg, #22C55E, #16A34A)',
    border: 'none',
    borderRadius: 8,
    color: 'white',
    fontSize: '0.875rem',
    fontWeight: 600,
    cursor: 'pointer',
    display: 'flex',
    alignItems: 'center',
    gap: '0.375rem',
  },
  spinner: {
    width: 16,
    height: 16,
    border: '2px solid rgba(255,255,255,0.3)',
    borderTopColor: 'white',
    borderRadius: '50%',
    animation: 'spin 0.8s linear infinite',
    display: 'inline-block',
  },
};
