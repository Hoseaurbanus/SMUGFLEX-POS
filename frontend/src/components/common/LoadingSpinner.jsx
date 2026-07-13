export default function LoadingSpinner({ fullScreen = false, size = 'md' }) {
  const sizes = { sm: 24, md: 40, lg: 56 };
  const px = sizes[size] || sizes.md;

  if (fullScreen) {
    return (
      <div className="loading-overlay">
        <div style={{ textAlign: 'center' }}>
          <div
            style={{
              width: px,
              height: px,
              border: '3px solid #334155',
              borderTopColor: '#2563EB',
              borderRadius: '50%',
              animation: 'spin 0.8s linear infinite',
              margin: '0 auto 1rem',
            }}
          />
          <p style={{ color: '#94A3B8', fontSize: '0.875rem' }}>Loading...</p>
        </div>
      </div>
    );
  }

  return (
    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '2rem' }}>
      <div
        style={{
          width: px,
          height: px,
          border: '3px solid #334155',
          borderTopColor: '#2563EB',
          borderRadius: '50%',
          animation: 'spin 0.8s linear infinite',
        }}
      />
    </div>
  );
}
