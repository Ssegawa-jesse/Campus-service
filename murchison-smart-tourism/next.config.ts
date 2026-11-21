import type { NextConfig } from 'next';

const nextConfig: NextConfig = {
  // The database driver uses a native module, so it is loaded at run time rather than bundled.
  serverExternalPackages: ['@libsql/client', 'libsql'],
  async headers() {
    return [
      // Allow H5P tours exported as HTML (public/h5p) to be shown in an iframe on our own pages.
      { source: '/h5p/:path*', headers: [{ key: 'X-Frame-Options', value: 'SAMEORIGIN' }] },
      { source: '/:path*', headers: [{ key: 'X-Content-Type-Options', value: 'nosniff' }, { key: 'Referrer-Policy', value: 'strict-origin-when-cross-origin' }] },
    ];
  },
};

export default nextConfig;
